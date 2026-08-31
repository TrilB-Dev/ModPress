const fs = require('fs');
const path = require('path');
const readline = require('readline');

const root = path.resolve(__dirname, '..');
const scriptPath = path.resolve(__filename);
const excludedDirectories = new Set(['.git', 'node_modules', 'vendor', 'dist', 'build']);
const binaryExtensions = new Set(['.7z', '.gif', '.ico', '.jpeg', '.jpg', '.mo', '.png', '.pdf', '.tar', '.woff', '.woff2', '.zip']);

function ask(rl, question, defaultValue = '') {
  const suffix = defaultValue ? ` [${defaultValue}]` : '';
  return new Promise((resolve) => {
    rl.question(`${question}${suffix}: `, (answer) => resolve(answer.trim() || defaultValue));
  });
}

function required(value, label, pattern, example) {
  if (!value || !pattern.test(value)) {
    throw new Error(`${label} must match ${example}.`);
  }
  return value;
}

function toSlug(value) {
  return value
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '')
    .replace(/-{2,}/g, '-');
}

function toIdentifier(value) {
  return value
    .replace(/[^a-zA-Z0-9]+(.)/g, (_, character) => character.toUpperCase())
    .replace(/[^a-zA-Z0-9]/g, '');
}

function toPascalCase(value) {
  return value
    .split(/[^a-zA-Z0-9]+/)
    .filter(Boolean)
    .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
    .join('');
}

function toPhpNamespace(value) {
  const namespace = value
    .split('\\')
    .flatMap((segment) => segment.split(/[^a-zA-Z0-9]+/))
    .filter(Boolean)
    .map((segment) => toPascalCase(segment))
    .join('\\');

  return namespace || 'Plugin';
}

function filesIn(directory) {
  return fs.readdirSync(directory, { withFileTypes: true }).flatMap((entry) => {
    const fullPath = path.join(directory, entry.name);
    if (entry.isDirectory()) {
      return excludedDirectories.has(entry.name) ? [] : filesIn(fullPath);
    }
    return fullPath === scriptPath ? [] : [fullPath];
  });
}

function isTextFile(filePath) {
  if (binaryExtensions.has(path.extname(filePath).toLowerCase())) return false;
  const buffer = fs.readFileSync(filePath);
  return !buffer.includes(0);
}

function escapeRegExp(value) {
  return value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

function replaceAll(value, replacements) {
  const replacementMap = new Map(replacements);
  const pattern = [...replacementMap.keys()]
    .sort((left, right) => right.length - left.length)
    .map(escapeRegExp)
    .join('|');

  return pattern ? value.replace(new RegExp(pattern, 'g'), (match) => replacementMap.get(match)) : value;
}

function relative(filePath) {
  return path.relative(root, filePath).replaceAll('\\', '/');
}

async function collectAnswers() {
  const rl = readline.createInterface({ input: process.stdin, output: process.stdout });
  try {
    const displayName = await ask(rl, 'Display name', 'Plugin Name');
    const slug = await ask(rl, 'Plugin slug (letters, digits, underscores, or hyphens)', toSlug(displayName));
    const description = await ask(rl, 'Description', `${displayName} is a WordPress plugin.`);
    const composerName = await ask(rl, 'Composer package name (vendor/package)', `author/${slug}`);
    const packageName = await ask(rl, 'npm package name', slug);
    const namespace = await ask(rl, 'PHP namespace (PascalCase segments; hyphens are normalized)', toPascalCase(displayName));
    const constantPrefix = await ask(rl, 'Constant prefix (uppercase)', slug.replace(/[^a-zA-Z0-9]+/g, '_').toUpperCase());
    const author = await ask(rl, 'Author', 'YourName');
    const authorEmail = await ask(rl, 'Author email', 'author@example.com');
    const authorUri = await ask(rl, 'Author URI', 'https://example.com');
    const pluginUri = await ask(rl, 'Plugin URI', `https://example.com/${slug}`);
    const textDomain = await ask(rl, 'Text domain (lowercase letters, digits, underscores, or hyphens)', slug);
    const cssJsPrefix = await ask(rl, 'CSS/JS prefix (lowercase)', slug);

    required(displayName, 'Display name', /^[a-zA-Z0-9 _-]+$/, 'letters, digits, underscores, hyphens, and spaces');
    required(slug, 'Plugin slug', /^[a-zA-Z0-9_-]+$/, 'letters, digits, underscores, or hyphens');
    required(description, 'Description', /\S/, 'at least one non-space character');
    required(composerName, 'Composer package name', /^[a-z0-9]+(?:[-_][a-z0-9]+)*\/[a-z0-9]+(?:[-_][a-z0-9]+)*$/, 'lowercase vendor/package');
    required(packageName, 'npm package name', /^(?:@[a-z0-9][a-z0-9._~-]*\/)?[a-z0-9][a-z0-9._~-]*$/, 'a valid lowercase npm package name');
    required(namespace, 'PHP namespace', /^[a-zA-Z][a-zA-Z0-9_ -]*(?:\\[a-zA-Z][a-zA-Z0-9_ -]*)*$/, 'namespace segments separated by backslashes');
    required(constantPrefix, 'Constant prefix', /^[A-Z][A-Z0-9_]*$/, 'an uppercase PHP identifier');
    required(authorEmail, 'Author email', /^[^\s@]+@[^\s@]+\.[^\s@]+$/, 'a valid email address');
    required(textDomain, 'Text domain', /^[a-z0-9_-]+$/, 'lowercase letters, digits, underscores, or hyphens');
    required(cssJsPrefix, 'CSS/JS prefix', /^[a-z][a-z0-9_-]*$/, 'lowercase letters, digits, underscores, or hyphens');

    return {
      displayName,
      slug,
      description,
      composerName,
      packageName,
      namespace,
      phpNamespace: toPhpNamespace(namespace),
      constantPrefix,
      textDomain,
      cssJsPrefix,
      shortcodePrefix: slug.replace(/-/g, '_'),
      shortcode: `${slug.replace(/-/g, '_')}_status`,
      bootstrapFilename: `${slug}.php`,
      assetBasename: slug,
      author,
      authorEmail,
      authorUri,
      pluginUri,
      compactSlug: slug.replaceAll('-', ''),
    };
  } finally {
    rl.close();
  }
}

function buildReplacements(values) {
  return [
    ['author/pluginname', values.composerName],
    ['pluginname/pluginname', values.composerName],
    ['"name": "pluginname"', `"name": ${JSON.stringify(values.packageName)}`],
    ['"description": "A WordPress plugin that provides"', `"description": ${JSON.stringify(values.description)}`],
    ['"description": "Wordpress Plugin"', `"description": ${JSON.stringify(values.description)}`],
    ['"email": "author@trilb.dev"', `"email": ${JSON.stringify(values.authorEmail)}`],
    ['"homepage": "https://author.com"', `"homepage": ${JSON.stringify(values.authorUri)}`],
    ['https://example.com/plugin-name', values.pluginUri],
    ['https://trilb.dev/collection/web-extension/wordpress/pluginname', values.pluginUri],
    ['https://trilb.dev/collection/web-extension/wordpress/plugin-name', values.pluginUri],
    ['Description:       PluginName is a WordPress plugin.', `Description:       ${values.description}`],
    ['PluginName\\PluginName', `${values.phpNamespace}\\Plugin`],
    ['namespace PluginName', `namespace ${values.phpNamespace}`],
    ['use PluginName\\', `use ${values.phpNamespace}\\`],
    ['\\PluginName\\', `\\${values.phpNamespace}\\`],
    ['Plugin Name:       PluginName', `Plugin Name:       ${values.displayName}`],
    ['PLUGINNAME', values.constantPrefix],
    ['Text Domain: pluginname', `Text Domain: ${values.textDomain}`],
    ['X-Domain: plugin-name', `X-Domain: ${values.textDomain}`],
    ["'pluginname'", `'${values.textDomain}'`],
    ['"pluginname"', `"${values.textDomain}"`],
    ['https://example.com', values.authorUri],
    ['https://trilb.dev', values.authorUri],
    ['Plugin Name', values.displayName],
    ['pluginname_status', values.shortcode],
    ['pluginname.js', `${values.assetBasename}.js`],
    ['pluginname.css', `${values.assetBasename}.css`],
    ['pluginname.php', values.bootstrapFilename],
    ['pluginname', values.slug],
    ['PluginName', values.phpNamespace],
    ['Author', values.author],
    ['MrTrilB', values.author],
  ];
}

function replacementsForFile(filePath, values) {
  const replacements = buildReplacements(values);
  if (/\.(css|js|scss|sass)$/.test(filePath)) {
    replacements.push([values.slug, values.cssJsPrefix]);
  }
  return replacements;
}

function replacementsForPath(filePath, values) {
  return replacementsForFile(filePath, {
    ...values,
    phpNamespace: values.phpNamespace.replaceAll('\\', ''),
  });
}

function planChanges(values) {
  const changes = [];

  for (const filePath of filesIn(root)) {
    const oldRelative = relative(filePath);
    const pathReplacements = replacementsForPath(oldRelative, values);
    const contentReplacements = replacementsForFile(oldRelative, values);
    let newRelative = replaceAll(oldRelative, pathReplacements);
    if (oldRelative === 'pluginname.php') {
      newRelative = values.bootstrapFilename;
    }
    if (newRelative !== oldRelative) {
      changes.push({ type: 'rename', from: oldRelative, to: newRelative });
    }

    if (isTextFile(filePath)) {
      const oldContent = fs.readFileSync(filePath, 'utf8');
      const newContent = replaceAll(oldContent, contentReplacements);
      if (newContent !== oldContent) {
        changes.push({ type: 'content', filePath: oldRelative, oldContent, newContent });
      }
    }
  }

  return { changes };
}

function printPlan(values, changes) {
  console.log('\nRename summary');
  console.log(`  Display name: ${values.displayName}`);
  console.log(`  Namespace:    ${values.namespace}`);
  console.log(`  PHP namespace:${values.phpNamespace}`);
  console.log(`  Slug:         ${values.slug}`);
  console.log(`  Constant:     ${values.constantPrefix}`);
  console.log(`  CSS/JS prefix:${values.cssJsPrefix}`);
  console.log(`  Composer:     ${values.composerName}`);
  console.log(`  npm package:  ${values.packageName}`);
  console.log(`\nPlanned changes: ${changes.length}`);

  for (const change of changes) {
    if (change.type === 'rename') {
      console.log(`  rename  ${change.from} -> ${change.to}`);
    } else {
      console.log(`  update  ${change.filePath}`);
    }
  }
}

function applyChanges(changes) {
  const renames = changes
    .filter((item) => item.type === 'rename')
    .sort((left, right) => right.from.length - left.from.length);
  const destinations = new Set();

  for (const change of renames) {
    const from = path.join(root, change.from);
    const to = path.join(root, change.to);
    if (destinations.has(change.to)) {
      throw new Error(`Cannot rename ${change.from}; destination is used more than once: ${change.to}`);
    }
    destinations.add(change.to);
    if (fs.existsSync(to) && path.resolve(from) !== path.resolve(to)) {
      throw new Error(`Cannot rename ${change.from}; destination already exists: ${change.to}`);
    }
  }

  for (const change of changes.filter((item) => item.type === 'content')) {
    const filePath = path.join(root, change.filePath);
    fs.writeFileSync(filePath, change.newContent, 'utf8');
  }

  for (const change of renames) {
    const from = path.join(root, change.from);
    const to = path.join(root, change.to);
    fs.renameSync(from, to);
  }
}

async function main() {
  if (process.argv.includes('--help') || process.argv.includes('-h')) {
    console.log('Usage: npm run rename [-- --dry-run]');
    console.log('');
    console.log('Guides you through renaming this WordPress plugin template.');
    console.log('The default mode previews changes and asks you to type APPLY.');
    console.log('--dry-run  Preview changes without asking for confirmation or writing files.');
    return;
  }

  const dryRunOnly = process.argv.includes('--dry-run');
  const applyImmediately = process.argv.includes('--apply');
  console.log('WordPress plugin rename assistant');
  console.log('This tool excludes .git, vendor, node_modules, compiled output, and itself.');
  console.log('The default mode previews changes and asks for confirmation. Use --dry-run to skip confirmation or --apply to apply after the questions.');

  const values = await collectAnswers();
  const { changes } = planChanges(values);
  printPlan(values, changes);

  if (changes.length === 0) {
    console.log('\nNo template placeholders were found.');
    return;
  }

  let shouldApply = applyImmediately;
  if (!dryRunOnly && !applyImmediately) {
    const rl = readline.createInterface({ input: process.stdin, output: process.stdout });
    const answer = await ask(rl, '\nType APPLY to perform these changes, or press Enter to leave the repo untouched');
    rl.close();
    shouldApply = answer === 'APPLY';
  }

  if (!shouldApply) {
    console.log('\nDry run complete. No files were changed.');
    return;
  }

  applyChanges(changes);
  console.log('\nRename complete. Run:');
  console.log('  composer dump-autoload');
  console.log('  npm run i18n:pot');
  console.log('  npm run i18n:mo');
  console.log('  npm run build');
}

main().catch((error) => {
  console.error(`\nRename failed: ${error.message}`);
  process.exitCode = 1;
});
