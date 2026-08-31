const fs = require('fs');
const path = require('path');

const root = path.resolve(__dirname, '..');
const sourceDirectory = path.join(root, 'src');
const languageDirectory = path.join(root, 'src', 'Languages');
const output = path.join(languageDirectory, 'ModPress.pot');
const ignoredDirectories = new Set(['vendor', 'node_modules', 'dist', 'build', 'Languages']);
const sourceExtensions = new Set(['.php', '.js', '.json']);

function filesIn(directory) {
  if (!fs.existsSync(directory)) return [];

  return fs.readdirSync(directory, { withFileTypes: true }).flatMap((entry) => {
    const fullPath = path.join(directory, entry.name);
    if (entry.isDirectory()) {
      return ignoredDirectories.has(entry.name) ? [] : filesIn(fullPath);
    }

    return sourceExtensions.has(path.extname(entry.name).toLowerCase()) ? [fullPath] : [];
  });
}

function decode(value) {
  return value.replace(/\\(['"\\])/g, '$1').replace(/\\n/g, '\n');
}

function addEntry(entries, sourceFile, line, singular, plural = null, context = null) {
  const key = JSON.stringify([singular, plural, context]);
  if (!entries.has(key)) {
    entries.set(key, { singular, plural, context, references: [] });
  }

  entries.get(key).references.push(`${sourceFile}:${line}`);
}

function extract(file, entries) {
  const content = fs.readFileSync(file, 'utf8');
  const relative = path.relative(root, file).replaceAll('\\', '/');
  const lines = content.split(/\r?\n/);
  const add = (line, singular, plural = null, context = null) => {
    if (singular && !/^https?:\/\//.test(singular)) {
      addEntry(
        entries,
        relative,
        line,
        decode(singular),
        plural ? decode(plural) : null,
        context ? decode(context) : null
      );
    }
  };

  const simple = /(?:__|_e|esc_html__|esc_attr__|esc_html_e|esc_attr_e)\s*\(\s*(['"])((?:\\.|(?!\1)[\s\S])*?)\1\s*,/g;
  const contextual = /_x\s*\(\s*(['"])((?:\\.|(?!\1)[\s\S])*?)\1\s*,\s*(['"])((?:\\.|(?!\3)[\s\S])*?)\3\s*,/g;
  const plural = /_n\s*\(\s*(['"])((?:\\.|(?!\1)[\s\S])*?)\1\s*,\s*(['"])((?:\\.|(?!\3)[\s\S])*?)\3\s*,/g;

  lines.forEach((lineText, index) => {
    let match;
    while ((match = contextual.exec(lineText)) !== null) {
      add(index + 1, match[2], null, match[4]);
    }
    while ((match = plural.exec(lineText)) !== null) {
      add(index + 1, match[2], match[4]);
    }
    while ((match = simple.exec(lineText)) !== null) {
      add(index + 1, match[2]);
    }

    contextual.lastIndex = 0;
    plural.lastIndex = 0;
    simple.lastIndex = 0;
  });
}

function quote(value) {
  return value.replace(/\\/g, '\\\\').replace(/"/g, '\\"').replace(/\n/g, '\\n');
}

function writeCatalog() {
  const entries = new Map();
  filesIn(sourceDirectory).forEach((file) => extract(file, entries));

  const sorted = [...entries.values()].sort((a, b) => a.singular.localeCompare(b.singular));
  const header = [
    '# Translation catalog for ModPress',
    '# Copyright (C) 2026 ModPress contributors',
    '# This file is distributed under the same license as the plugin.',
    'msgid ""',
    'msgstr ""',
    '"Project-Id-Version: ModPress 1.0.0\\n"',
    '"Content-Type: text/plain; charset=UTF-8\\n"',
    '"Content-Transfer-Encoding: 8bit\\n"',
    '"X-Domain: modpress\\n"',
    '',
  ];

  const body = sorted.map((entry) => {
    const references = [...new Set(entry.references)].sort().map((reference) => `#: ${reference}`).join('\n');
    const lines = [references];
    if (entry.context) lines.push(`msgctxt "${quote(entry.context)}"`);
    lines.push(`msgid "${quote(entry.singular)}"`);
    if (entry.plural) {
      lines.push(`msgid_plural "${quote(entry.plural)}"`, 'msgstr[0] ""', 'msgstr[1] ""');
    } else {
      lines.push('msgstr ""');
    }
    return lines.join('\n');
  }).join('\n\n');

  fs.mkdirSync(languageDirectory, { recursive: true });
  fs.writeFileSync(output, `${header.join('\n')}${body}\n`, 'utf8');
  console.log(`${path.relative(root, output)}: ${sorted.length} entries`);
}

writeCatalog();
