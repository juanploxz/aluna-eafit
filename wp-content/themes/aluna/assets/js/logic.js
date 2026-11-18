/* Pure domain functions, shared by the browser and dependency-free Node tests. */
(function (root) {
  "use strict";
  const normalize = (value) =>
    String(value ?? "")
      .normalize("NFD")
      .replace(/[\u0300-\u036f]/g, "")
      .toLowerCase()
      .trim();
  const fits = (p, skin) =>
    skin === "all" || p.skin.includes("todas") || p.skin.includes(skin);
  function filter(products, state) {
    const query = normalize(state.query);
    const found = products.filter(
      (p) =>
        (state.category === "all" || p.category === state.category) &&
        fits(p, state.skin) &&
        normalize(
          [p.name, p.description, p.ingredients, p.categoryLabel].join(" "),
        ).includes(query),
    );
    if (state.sort === "price-asc") found.sort((a, b) => a.price - b.price);
    else if (state.sort === "price-desc")
      found.sort((a, b) => b.price - a.price);
    else if (state.sort === "name")
      found.sort((a, b) => a.name.localeCompare(b.name, "es"));
    else found.sort((a, b) => Number(b.featured) - Number(a.featured));
    return found;
  }
  function routine(products, skin, concern) {
    const eligible = products.filter((p) => fits(p, skin));
    const score = (p) =>
      (p.concern === concern ? 8 : 0) +
      (p.skin.includes(skin) ? 3 : 0) +
      Number(p.featured);
    const pick = (category) =>
      eligible
        .filter((p) => p.category === category)
        .sort((a, b) => score(b) - score(a))[0];
    const chosen = [pick("limpieza")];
    if (concern !== "simple") {
      const treatment = eligible
        .filter((p) => p.category === "tratamientos" && p.concern === concern)
        .sort((a, b) => score(b) - score(a))[0];
      if (treatment) chosen.push(treatment);
    }
    chosen.push(pick("hidratacion"), pick("proteccion"));
    return chosen.filter(Boolean);
  }
  function favoriteIds(raw, products) {
    try {
      const value = JSON.parse(raw);
      if (!Array.isArray(value)) return [];
      const allowed = new Set(products.map((p) => String(p.id)));
      return [
        ...new Set(
          value
            .filter(
              (id) =>
                typeof id === "string" ||
                (typeof id === "number" && Number.isSafeInteger(id) && id > 0),
            )
            .map(String)
            .filter((id) => allowed.has(id)),
        ),
      ].slice(0, 100);
    } catch {
      return [];
    }
  }
  const api = { normalize, filter, routine, favoriteIds };
  if (typeof module !== "undefined" && module.exports) module.exports = api;
  else root.AlunaLogic = api;
})(globalThis);
