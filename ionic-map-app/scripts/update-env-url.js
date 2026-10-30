const fs = require('fs');
const path = require('path');
const { spawn } = require('child_process');

function getUrlFromArgs() {
  const args = process.argv.slice(2);

  for (let i = 0; i < args.length; i += 1) {
    const arg = args[i];

    if (arg === '--url' && args[i + 1]) {
      return args[i + 1];
    }

    if (arg.startsWith('--url=')) {
      return arg.split('=')[1];
    }
  }

  return null;
}

function getNgServeArgs() {
  const args = process.argv.slice(2);
  const filtered = [];

  for (let i = 0; i < args.length; i += 1) {
    const arg = args[i];

    if (arg === '--url') {
      i += 1;
      continue;
    }

    if (arg.startsWith('--url=')) {
      continue;
    }

    filtered.push(arg);
  }

  return filtered;
}

const targetFile = path.join(__dirname, '..', 'src', 'environments', 'environment.ts');
const chosenUrl = getUrlFromArgs() || process.env.APP_URL || 'http://localhost:4200';

const currentContent = fs.readFileSync(targetFile, 'utf8');
const updatedContent = currentContent.replace(
  /const baseUrl = .*?;/,
  `const baseUrl = '${chosenUrl}';`
);

fs.writeFileSync(targetFile, updatedContent, 'utf8');
console.log(`Updated baseUrl in src/environments/environment.ts to ${chosenUrl}`);

const ngPath = path.join(__dirname, '..', 'node_modules', '@angular', 'cli', 'bin', 'ng');
const child = spawn(process.execPath, [ngPath, 'serve', ...getNgServeArgs()], {
  stdio: 'inherit'
});

child.on('exit', (code) => {
  process.exit(code ?? 0);
});

child.on('error', (error) => {
  console.error(error);
  process.exit(1);
});
