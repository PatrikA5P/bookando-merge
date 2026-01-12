// src/Core/Design/assets/vue/ui.js
// Registriert ALLE Vue-Komponenten unter ../../components automatisch.
// → robust gegen fehlende component.name, registriert PascalCase + kebab-case.

function toPascalCase(fname) {
  // "app-table-sticky-test" → "AppTableStickyTest"
  return fname
    .replace(/\.vue$/i, "")
    .split(/[-_]/g)
    .filter(Boolean)
    .map(s => s.charAt(0).toUpperCase() + s.slice(1))
    .join("");
}

function toKebabCase(name) {
  // "AppTableStickyTest" → "app-table-sticky-test"
  return name
    .replace(/([a-z0-9])([A-Z])/g, "$1-$2")
    .replace(/_{1,}/g, "-")
    .toLowerCase();
}

export default {
  install(app) {
    // Wichtig: relativer Pfad, damit Vite & das Audit-Skript alles sicher finden
    const modules = import.meta.glob("../../components/**/*.vue", { eager: true });

    for (const [file, mod] of Object.entries(modules)) {
      const comp = mod && (mod.default || mod);
      if (!comp) continue;

      // 1) Name aus SFC, sonst aus Dateiname ableiten
      let name = comp.name;
      if (!name) {
        const fname = file.split("/").pop() || "";
        name = toPascalCase(fname);
      }

      // OPTIONAL: bestimmte Dateien ausschliessen (z. B. Demos/Experimente)
      // if (/TableStickyTest\.vue$/i.test(file)) continue;

      // 2) PascalCase + kebab-case registrieren (robust für <template>-Nutzung)
      const kebab = toKebabCase(name);
      if (!app.component(name))  app.component(name, comp);
      if (!app.component(kebab)) app.component(kebab, comp);
    }
  }
};

