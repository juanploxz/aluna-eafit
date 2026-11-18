(() => {
  "use strict";
  const dataEl = document.getElementById("aluna-data");
  if (!dataEl || !window.AlunaLogic) return;
  let data;
  try {
    data = JSON.parse(dataEl.textContent);
  } catch {
    return;
  }
  const products = Array.isArray(data.products) ? data.products : [];
  const logic = window.AlunaLogic;
  const $ = (selector, parent = document) => parent.querySelector(selector);
  const $$ = (selector, parent = document) => [
    ...parent.querySelectorAll(selector),
  ];
  const escape = (value) =>
    String(value ?? "").replace(
      /[&<>"']/g,
      (c) =>
        ({
          "&": "&amp;",
          "<": "&lt;",
          ">": "&gt;",
          '"': "&quot;",
          "'": "&#39;",
        })[c],
    );
  const money = (value) =>
    new Intl.NumberFormat("es-CO", {
      style: "currency",
      currency: "COP",
      maximumFractionDigits: 0,
    }).format(value);
  const arrow =
    '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true"><path d="M4 12h16m-6-6 6 6-6 6"/></svg>';
  const heart =
    '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8L12 21l8.8-8.6a5.5 5.5 0 0 0 0-7.8Z"/></svg>';
  const plus =
    '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true"><path d="M5 12h14M12 5v14"/></svg>';
  const closeIcon =
    '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true"><path d="m6 6 12 12M6 18 18 6"/></svg>';
  const findProduct = (id) => products.find((p) => String(p.id) === String(id));
  const storageKey = "aluna.favorites.v1";
  let storageAvailable = true;
  let favorites = [];
  try {
    favorites = logic.favoriteIds(localStorage.getItem(storageKey), products);
  } catch {
    storageAvailable = false;
  }
  let toastTimer;
  function notify(message) {
    const toast = $("#toast");
    const container = $("dialog[open]") || document.body;
    container.append(toast);
    toast.textContent = message;
    toast.hidden = false;
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => {
      toast.hidden = true;
    }, 3500);
  }
  function updateFavoriteButtons() {
    $$("[data-favorite]").forEach((button) => {
      const saved = favorites.includes(String(button.dataset.favorite));
      button.setAttribute("aria-pressed", String(saved));
      const p = findProduct(button.dataset.favorite);
      if (p)
        button.setAttribute(
          "aria-label",
          `${saved ? "Quitar" : "Guardar"} ${p.name} ${saved ? "de" : "en"} favoritos`,
        );
      const label = $("[data-favorite-label]", button);
      if (label)
        label.textContent = saved
          ? "Guardado en favoritos"
          : "Guardar en favoritos";
    });
    $$("[data-favorites-count]").forEach((count) => {
      count.textContent = favorites.length;
      count.hidden = !favorites.length;
    });
    $$("[data-favorites-open]").forEach((button) =>
      button.setAttribute(
        "aria-label",
        `Ver favoritos${favorites.length ? `, ${favorites.length} guardados` : ""}`,
      ),
    );
  }
  function saveFavorites() {
    try {
      localStorage.setItem(storageKey, JSON.stringify(favorites));
    } catch {
      storageAvailable = false;
    }
    updateFavoriteButtons();
  }
  function toggleFavorite(id) {
    if (!findProduct(id)) return;
    id = String(id);
    const saved = favorites.includes(id);
    favorites = saved
      ? favorites.filter((item) => item !== id)
      : [...favorites, id];
    saveFavorites();
    if ($("#favorites-dialog").open) {
      const index = Math.max(
        0,
        $$("[data-favorite]", $("#favorites-list")).findIndex(
          (b) => b.dataset.favorite === id,
        ),
      );
      renderFavorites();
      const buttons = $$("[data-favorite]", $("#favorites-list"));
      const next =
        buttons[Math.min(index, buttons.length - 1)] || $("#favorites-title");
      if (next) {
        next.setAttribute("tabindex", next.tagName === "BUTTON" ? "0" : "-1");
        next.focus();
      }
    }
    notify(
      storageAvailable
        ? saved
          ? "Producto eliminado de tus favoritos."
          : "Producto guardado en tus favoritos."
        : "Tus favoritos estarán disponibles solo durante esta visita.",
    );
  }
  const focusOrigins = new WeakMap();
  function openDialog(dialog, origin = document.activeElement) {
    const previous = $("dialog[open]");
    if (previous && previous.contains(origin))
      origin = focusOrigins.get(previous) || $("[data-favorites-open]");
    $$("dialog[open]").forEach((open) => open.close());
    focusOrigins.set(dialog, origin);
    dialog.showModal();
    document.body.classList.add("modal-open");
    dialog.scrollTop = 0;
  }
  $$("dialog").forEach((dialog) => {
    dialog.addEventListener("click", (event) => {
      if (event.target !== dialog) return;
      const rect = dialog.getBoundingClientRect();
      if (
        event.clientX < rect.left ||
        event.clientX > rect.right ||
        event.clientY < rect.top ||
        event.clientY > rect.bottom
      )
        dialog.close();
    });
    dialog.addEventListener("close", () => {
      if (!$("dialog[open]")) {
        document.body.classList.remove("modal-open");
        const origin = focusOrigins.get(dialog);
        if (origin?.isConnected && origin.getClientRects().length)
          origin.focus();
      }
    });
  });
  function showProduct(id, origin) {
    const p = findProduct(id);
    if (!p) return;
    $("#product-detail").innerHTML =
      `<div class="detail-layout"><div class="detail-image"><img src="${escape(p.imageUrl)}" alt="${escape(p.name)}" width="700" height="700"></div><div class="detail-copy"><p class="eyebrow">${escape(p.categoryLabel)}</p><h2 id="detail-name">${escape(p.name)}</h2><p class="detail-size">${escape(p.size)}</p><p class="detail-description">${escape(p.description)}</p><p class="detail-price">${money(p.price)}<span>COP</span></p><button type="button" class="button primary" data-favorite="${escape(p.id)}" aria-pressed="false">${heart}<span data-favorite-label>Guardar en favoritos</span></button><div class="detail-accordions"><details open><summary>Cómo usarlo${plus}</summary><p>${escape(p.usage)}</p></details><details><summary>Ingredientes destacados${plus}</summary><p>${escape(p.ingredients)}</p></details><details><summary>¿Para qué tipo de piel?${plus}</summary><p>${escape(p.skin.map((s) => ({ todas: "Todos los tipos", seca: "Seca", mixta: "Mixta", grasa: "Grasa", sensible: "Sensible" })[s] || s).join(" · "))}</p></details></div><a class="text-button" href="${escape(p.url)}">Abrir ficha completa ${arrow}</a></div></div>`;
    updateFavoriteButtons();
    openDialog($("#product-dialog"), origin);
  }
  function renderFavorites() {
    const selected = favorites.map(findProduct).filter(Boolean);
    $("#favorites-list").innerHTML = selected.length
      ? selected
          .map(
            (p) =>
              `<article class="favorite-item"><a href="${escape(p.url)}" data-product-open="${escape(p.id)}" tabindex="-1" aria-hidden="true"><img src="${escape(p.imageUrl)}" alt="" width="80" height="90"></a><div><h3><a href="${escape(p.url)}" data-product-open="${escape(p.id)}">${escape(p.name)}</a></h3><p>${money(p.price)} COP · ${escape(p.size)}</p></div><button class="icon-button" type="button" data-favorite="${escape(p.id)}" aria-label="Quitar ${escape(p.name)} de favoritos">${closeIcon}</button></article>`,
          )
          .join("")
      : `<div class="favorite-empty">${heart}<h3>Lo que te gusta, cerca.</h3><p>Toca el corazón de un producto para guardarlo en tu selección.</p><a class="button primary" href="${escape(data.home)}#coleccion" data-browse>Explorar la colección ${arrow}</a></div>`;
    updateFavoriteButtons();
  }
  const state = { query: "", category: "all", skin: "all", sort: "featured" };
  const grid = $("#product-grid");
  function applyFilters(updateUrl = true) {
    if (!grid) return;
    const filtered = logic.filter(products, state);
    const visible = new Set(filtered.map((p) => String(p.id)));
    const cards = new Map(
      $$("[data-product-card]", grid).map((card) => [card.dataset.id, card]),
    );
    cards.forEach((card, id) => {
      card.hidden = !visible.has(id);
    });
    filtered.forEach((p) => {
      const card = cards.get(String(p.id));
      if (card) grid.append(card);
    });
    $("#catalog-empty").hidden = filtered.length > 0;
    $("#result-count").textContent =
      `${filtered.length} ${filtered.length === 1 ? "esencial" : "esenciales"}`;
    $$("[data-category-filter]").forEach((button) =>
      button.setAttribute(
        "aria-pressed",
        String(button.dataset.categoryFilter === state.category),
      ),
    );
    if (updateUrl) {
      const url = new URL(location.href);
      for (const [key, value] of Object.entries({
        buscar: state.query,
        categoria: state.category,
        piel: state.skin,
        orden: state.sort,
      })) {
        if (!value || value === "all" || value === "featured")
          url.searchParams.delete(key);
        else url.searchParams.set(key, value);
      }
      history.replaceState(null, "", url);
    }
  }
  if (grid) {
    const params = new URLSearchParams(location.search);
    state.query = (params.get("buscar") || "").slice(0, 100);
    if (
      ["limpieza", "hidratacion", "tratamientos", "proteccion"].includes(
        params.get("categoria"),
      )
    )
      state.category = params.get("categoria");
    if (["seca", "mixta", "grasa", "sensible"].includes(params.get("piel")))
      state.skin = params.get("piel");
    if (["price-asc", "price-desc", "name"].includes(params.get("orden")))
      state.sort = params.get("orden");
    $("#catalog-search").value = state.query;
    $("#skin-filter").value = state.skin;
    $("#sort-products").value = state.sort;
    applyFilters(false);
    $("#catalog-search").addEventListener("input", (event) => {
      state.query = event.target.value;
      applyFilters();
    });
    $("#skin-filter").addEventListener("change", (event) => {
      state.skin = event.target.value;
      applyFilters();
    });
    $("#sort-products").addEventListener("change", (event) => {
      state.sort = event.target.value;
      applyFilters();
    });
    $(".catalog-tools").addEventListener("submit", (event) => {
      event.preventDefault();
      applyFilters();
    });
  }
  const quiz = { step: 0, skin: "", concern: "" };
  const skinLabels = {
    seca: "seca",
    mixta: "mixta",
    grasa: "grasa",
    sensible: "sensible",
  };
  function renderQuiz(focus = true) {
    const isSkin = quiz.step === 0;
    const options = isSkin
      ? [
          ["seca", "Seca", "Suele sentirse tirante"],
          ["mixta", "Mixta", "Cambia según la zona"],
          ["grasa", "Grasa", "Suele tener brillo"],
          ["sensible", "Sensible", "Prefiere un cuidado suave"],
        ]
      : [
          ["hidratacion", "Hidratación", "Una sensación de confort"],
          ["equilibrio", "Equilibrio", "Texturas ligeras"],
          ["luminosidad", "Luminosidad", "Un gesto de cuidado extra"],
          ["simple", "Lo esencial", "Una rutina de pocos pasos"],
        ];
    $("#quiz-content").innerHTML =
      `<div class="quiz-progress" aria-hidden="true"><span class="active"></span><span class="${isSkin ? "" : "active"}"></span></div><p class="eyebrow">TU RITUAL · PASO ${quiz.step + 1} DE 2</p><h2 id="quiz-title" tabindex="-1">${isSkin ? "Empecemos por tu piel." : "¿Qué quieres priorizar?"}</h2><p class="quiz-intro">${isSkin ? "Elige la opción con la que más te identificas." : "No necesitas hacerlo todo. Elige lo que más te importa."}</p><div class="quiz-options" role="group" aria-label="${isSkin ? "Tipo de piel" : "Prioridad de cuidado"}">${options.map(([value, label, help]) => `<button class="quiz-option" type="button" data-quiz-choice="${value}" aria-pressed="${String(value === (isSkin ? quiz.skin : quiz.concern))}">${label}<span>${help}</span></button>`).join("")}</div><div class="quiz-controls"><button class="text-button" type="button" ${isSkin ? "data-close" : "data-quiz-back"}>${isSkin ? "Ahora no" : "← Atrás"}</button><button class="button primary" type="button" data-quiz-next ${!(isSkin ? quiz.skin : quiz.concern) ? "disabled" : ""}>${isSkin ? "Continuar" : "Ver mi ritual"} ${arrow}</button></div>`;
    if (focus) $("#quiz-title").focus();
  }
  function renderRoutine() {
    const selected = logic.routine(products, quiz.skin, quiz.concern);
    const goal = {hidratacion: 'hidratación', equilibrio: 'equilibrio', luminosidad: 'luminosidad', simple: 'lo esencial'}[quiz.concern];
    const exactMatch = quiz.concern === 'simple' || selected.some(p => p.concern === quiz.concern);
    const selectionNote = selected.length
      ? `Tu selección para piel ${escape(skinLabels[quiz.skin])}, con prioridad en ${goal}.${exactMatch ? '' : ' Aún no hay un tratamiento específico compatible con esta prioridad; te mostramos los pasos básicos disponibles.'}`
      : 'Aún no hay productos disponibles para esta selección.';
    const steps = {
      limpieza: "01 · Limpia",
      tratamientos: "02 · Cuida",
      hidratacion: "03 · Hidrata",
      proteccion: "04 · Protege",
    };
    $("#quiz-content").innerHTML =
      `<p class="eyebrow">HECHO A TU MEDIDA</p><h2 id="quiz-title" tabindex="-1">Un buen comienzo.</h2><p class="quiz-intro">${selectionNote}</p><div class="quiz-result">${selected.map((p) => `<a class="routine-item" href="${escape(p.url)}" data-product-open="${escape(p.id)}"><img src="${escape(p.imageUrl)}" alt="" width="54" height="64"><div><span>${steps[p.category] || ""}</span><h3>${escape(p.name)}</h3></div>${arrow}</a>`).join("")}</div><div class="quiz-result-actions">${selected.length ? '<button class="button primary" type="button" data-save-routine>Guardar mi selección</button>' : ""}<button class="button secondary" type="button" data-quiz-restart>Volver a empezar</button></div><p class="local-note">Selección orientativa. No sustituye una consulta profesional.</p>`;
    $("#quiz-title").focus();
  }
  document.addEventListener("click", (event) => {
    const button = event.target.closest("button,a");
    if (!button) return;
    if (button.matches("[data-close]")) {
      button.closest("dialog")?.close();
      return;
    }
    if (button.matches("[data-favorite]")) {
      toggleFavorite(button.dataset.favorite);
      return;
    }
    if (button.matches("[data-product-open]")) {
      if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey)
        return;
      event.preventDefault();
      showProduct(button.dataset.productOpen, button);
      return;
    }
    if (button.matches("[data-favorites-open]")) {
      renderFavorites();
      openDialog($("#favorites-dialog"), button);
      return;
    }
    if (button.matches("[data-privacy-open]")) {
      openDialog($("#privacy-dialog"), button);
      return;
    }
    if (button.matches("[data-clear-favorites]")) {
      favorites = [];
      saveFavorites();
      notify("Tus favoritos se han borrado.");
      return;
    }
    if (button.matches("[data-browse]")) {
      $$("dialog[open]").forEach((d) => d.close());
    }
    if (button.matches("[data-search-open]")) {
      if (grid) {
        $("#coleccion").scrollIntoView();
        $("#catalog-search").focus({ preventScroll: true });
      } else location.href = `${data.home}#coleccion`;
      return;
    }
    if (button.matches("[data-category-filter]")) {
      state.category = button.dataset.categoryFilter;
      applyFilters();
      return;
    }
    if (button.matches("[data-reset-filters]")) {
      Object.assign(state, {
        query: "",
        category: "all",
        skin: "all",
        sort: "featured",
      });
      $("#catalog-search").value = "";
      $("#skin-filter").value = "all";
      $("#sort-products").value = "featured";
      applyFilters();
      return;
    }
    if (button.matches("[data-quiz-open]")) {
      quiz.step = 0;
      renderQuiz(false);
      openDialog($("#quiz-dialog"), button);
      return;
    }
    if (button.matches("[data-quiz-choice]")) {
      quiz[quiz.step === 0 ? "skin" : "concern"] = button.dataset.quizChoice;
      $$("[data-quiz-choice]").forEach((b) =>
        b.setAttribute("aria-pressed", String(b === button)),
      );
      $("[data-quiz-next]").disabled = false;
      return;
    }
    if (button.matches("[data-quiz-next]")) {
      if (quiz.step === 0 && quiz.skin) {
        quiz.step = 1;
        renderQuiz();
      } else if (quiz.concern) renderRoutine();
      return;
    }
    if (button.matches("[data-quiz-back]")) {
      quiz.step = 0;
      renderQuiz();
      return;
    }
    if (button.matches("[data-quiz-restart]")) {
      Object.assign(quiz, { step: 0, skin: "", concern: "" });
      renderQuiz();
      return;
    }
    if (button.matches("[data-save-routine]")) {
      favorites = [
        ...new Set([
          ...favorites,
          ...logic
            .routine(products, quiz.skin, quiz.concern)
            .map((p) => String(p.id)),
        ]),
      ];
      saveFavorites();
      notify(
        storageAvailable
          ? "Tu ritual está guardado en favoritos."
          : "Tu selección estará disponible durante esta visita.",
      );
      return;
    }
  });
  const menuToggle = $(".menu-toggle");
  const menu = $("#main-nav");
  function closeMenu() {
    menu.classList.remove("is-open");
    menuToggle.setAttribute("aria-expanded", "false");
    menuToggle.setAttribute("aria-label", "Abrir menú");
  }
  menuToggle?.addEventListener("click", () => {
    const open = !menu.classList.contains("is-open");
    menu.classList.toggle("is-open", open);
    menuToggle.setAttribute("aria-expanded", String(open));
    menuToggle.setAttribute("aria-label", open ? "Cerrar menú" : "Abrir menú");
  });
  menu?.addEventListener("click", (event) => {
    if (event.target.closest("a")) closeMenu();
  });
  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape" && menu.classList.contains("is-open")) {
      closeMenu();
      menuToggle.focus();
    }
  });
  document.addEventListener("click", (event) => {
    if (
      menu?.classList.contains("is-open") &&
      !event.target.closest(".site-header")
    )
      closeMenu();
  });
  window.addEventListener("storage", (event) => {
    if (event.key === storageKey || event.key === null) {
      favorites = logic.favoriteIds(event.newValue, products);
      updateFavoriteButtons();
      if ($("#favorites-dialog").open) renderFavorites();
    }
  });
  updateFavoriteButtons();
})();
