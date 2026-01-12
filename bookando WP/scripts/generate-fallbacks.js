#!/usr/bin/env node
//generate-falbacks.js


/**
 * Bookando Fallback-Template Generator
 * Erzeugt PHP-Fallback-Templates (list.php, form.php, _filterbar.php, _table.php)
 * für alle Module unter /src/modules/<slug>/, sofern sie noch nicht existieren.
 */

import fs from 'fs'
import path from 'path'

const MODULES_DIR = path.resolve(__dirname, '../src/modules')

// Utility: Extrahiere Feldnamen aus Model.php (Fallback: module.json)
function extractFields(modPath) {
  // 1. Versuche Model.php zu lesen und Felder zu extrahieren
  const modelFile = path.join(modPath, 'Model.php')
  if (fs.existsSync(modelFile)) {
    const content = fs.readFileSync(modelFile, 'utf-8')
    const props = Array.from(content.matchAll(/\*\s*@property\s+\w+\s+\$(\w+)/g)).map(m => m[1])
    if (props.length) return props
  }
  // 2. Fallback: Felder aus module.json ableiten
  const moduleJson = path.join(modPath, 'module.json')
  if (fs.existsSync(moduleJson)) {
    try {
      const meta = JSON.parse(fs.readFileSync(moduleJson, 'utf-8'))
      if (meta && meta.slug) {
        // Heuristik: id, title, status, created_at
        return ['id', 'title', 'status', 'created_at']
      }
    } catch (e) {
      // Fallback greift unten – Fehler bewusst ignorieren
      void e;
    }
  }
  return ['id', 'title', 'status']
}

function writeFallback(filepath, content) {
  if (fs.existsSync(filepath)) {
    console.warn(`⚠️  Datei existiert schon und wird nicht überschrieben: ${filepath}`)
    return
  }
  fs.writeFileSync(filepath, content)
}

// Main
fs.readdirSync(MODULES_DIR).forEach(slug => {
  const modPath = path.join(MODULES_DIR, slug)
  if (!fs.statSync(modPath).isDirectory()) return
  const fields = extractFields(modPath)
  const human = f => f.replace(/_/g, ' ').replace(/\bid\b/i, 'ID').replace(/\b([a-z])/g, c => c.toUpperCase())

  // list.php
  if (!fs.existsSync(path.join(modPath, 'Templates'))) fs.mkdirSync(path.join(modPath, 'Templates'))
  if (!fs.existsSync(path.join(modPath, 'Templates/list.php'))) {
    writeFallback(
      path.join(modPath, 'Templates/list.php'),
      `<?php
/**
 * [AUTOMATISCH ERZEUGT – Nicht manuell bearbeiten!]
 * List-Fallback für Modul "${slug}" (Bookando)
 */
?>
<div class="bookando-table-container">
  <a class="button button-primary" href="?page=bookando_${slug}&action=new"><?php esc_html_e('Neuer Eintrag', 'bookando'); ?></a>
  <table class="bookando-table" style="margin-top:1.5rem;">
    <thead>
      <tr>
        ${fields.map(f => `<th><?php esc_html_e('${human(f)}', 'bookando'); ?></th>`).join('\n        ')}
        <th><?php esc_html_e('Aktionen', 'bookando'); ?></th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($items)) : ?>
        <tr><td colspan="${fields.length+1}"><?php esc_html_e('Keine Einträge gefunden.', 'bookando'); ?></td></tr>
      <?php else : foreach ($items as $item) : ?>
        <tr>
          ${fields.map(f => `<td><?php echo esc_html($item['${f}'] ?? ''); ?></td>`).join('\n          ')}
          <td>
            <a href="?page=bookando_${slug}&action=edit&id=<?php echo $item['id']; ?>" class="button"><?php esc_html_e('Bearbeiten', 'bookando'); ?></a>
            <a href="?page=bookando_${slug}&action=delete&id=<?php echo $item['id']; ?>" class="button button-danger" onclick="return confirm('Wirklich löschen?')"><?php esc_html_e('Löschen', 'bookando'); ?></a>
          </td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>
`
    )
  }

  // form.php
  if (!fs.existsSync(path.join(modPath, 'Templates/form.php'))) {
    writeFallback(
      path.join(modPath, 'Templates/form.php'),
      `<?php
/**
 * [AUTOMATISCH ERZEUGT – Nicht manuell bearbeiten!]
 * Form-Fallback für Modul "${slug}" (Bookando)
 */
?>
<form method="post" action="">
  <?php wp_nonce_field('bookando_save_${slug}'); ?>
  ${fields.filter(f=>f!=='id'&&f!=='created_at').map(f=>`
  <div class="bookando-form-row">
    <label><?php esc_html_e('${human(f)}', 'bookando'); ?></label>
    <input type="text" name="${f}" value="<?php echo esc_attr($item['${f}'] ?? ''); ?>">
  </div>`).join('')}
  <button class="button button-primary"><?php esc_html_e('Speichern', 'bookando'); ?></button>
</form>
`
    )
  }

  // _filterbar.php
  if (!fs.existsSync(path.join(modPath, 'Templates/_filterbar.php'))) {
    writeFallback(
      path.join(modPath, 'Templates/_filterbar.php'),
      `<?php
/**
 * [AUTOMATISCH ERZEUGT – Nicht manuell bearbeiten!]
 * Filterbar-Fallback für Modul "${slug}" (Bookando)
 */
?>
<div class="bookando-filterbar">
  <!-- Beispiel: Filter nach Name, Status -->
  <input type="text" name="filter_title" placeholder="<?php esc_attr_e('Titel suchen', 'bookando'); ?>">
  <select name="filter_status">
    <option value=""><?php esc_html_e('Alle Stati', 'bookando'); ?></option>
    <option value="aktiv"><?php esc_html_e('Aktiv', 'bookando'); ?></option>
    <option value="inaktiv"><?php esc_html_e('Inaktiv', 'bookando'); ?></option>
  </select>
  <button class="button"><?php esc_html_e('Filtern', 'bookando'); ?></button>
</div>
`
    )
  }

  // _table.php
  if (!fs.existsSync(path.join(modPath, 'Templates/_table.php'))) {
    writeFallback(
      path.join(modPath, 'Templates/_table.php'),
      `<?php
/**
 * [AUTOMATISCH ERZEUGT – Nicht manuell bearbeiten!]
 * Table-Partial-Fallback für Modul "${slug}" (Bookando)
 */
?>
<table class="bookando-table">
  <thead>
    <tr>
      ${fields.map(f => `<th><?php esc_html_e('${human(f)}', 'bookando'); ?></th>`).join('\n      ')}
    </tr>
  </thead>
  <tbody>
    <?php if (empty($items)) : ?>
      <tr><td colspan="${fields.length}"><?php esc_html_e('Keine Einträge gefunden.', 'bookando'); ?></td></tr>
    <?php else : foreach ($items as $item) : ?>
      <tr>
        ${fields.map(f => `<td><?php echo esc_html($item['${f}'] ?? ''); ?></td>`).join('\n        ')}
      </tr>
    <?php endforeach; endif; ?>
  </tbody>
</table>
`
    )
  }
})

console.log('✅ PHP-Fallback-Templates für alle Module generiert (nur neue Dateien, keine Überschreibungen)!')
