#!/usr/bin/env node
// Content backstop for Claude Code's PreToolUse hook.
// Pure Node (no bash/jq) — runs cross-platform since Claude Code already
// depends on Node to run itself; no separate Node project needed for this
// PHP application.
//
// Fails CLOSED: if input can't be parsed or something errors, this blocks
// rather than waving the action through. Exit code 2 blocks the tool call.

import { readFileSync } from 'node:fs';

// Adjust for this project's real off-limits paths.
const PROTECTED = [
  /(^|\/)vendor\//,
  /(^|\/)\.git\//,
  /(^|\/)database\/backups\//,
];

const DESTRUCTIVE_COMMANDS = [
  /\brm\s+-rf\b/,
  /\bgit\s+push\s+--force\b/,
  /\bgit\s+reset\s+--hard\b/,
  /\bDROP\s+TABLE\b/i,
  /\bTRUNCATE\b/i,
];

const ENV_FILE = /(^|\/)\.env($|\.)(?!example)/;

function block(reason) {
  console.error(`✗ guard.mjs blocked this action: ${reason}`);
  process.exit(2);
}

try {
  const raw = readFileSync(0, 'utf-8'); // hook input on stdin
  const input = JSON.parse(raw);

  const toolName = input?.tool_name ?? '';
  const toolInput = input?.tool_input ?? {};
  const command = toolInput?.command ?? '';
  const filePath = toolInput?.file_path ?? toolInput?.path ?? '';

  if (command) {
    for (const pattern of DESTRUCTIVE_COMMANDS) {
      if (pattern.test(command)) {
        block(`destructive command matched ${pattern}`);
      }
    }
  }

  if (filePath) {
    if (ENV_FILE.test(filePath)) {
      block(`access to a real .env file (${filePath}) — .env.example is the exception`);
    }
    for (const pattern of PROTECTED) {
      if (pattern.test(filePath)) {
        block(`protected path matched ${pattern}: ${filePath}`);
      }
    }
  }

  // Passed all checks.
  process.exit(0);
} catch (err) {
  // Fail closed — can't verify safety, so don't allow it through.
  console.error(`✗ guard.mjs error, failing closed: ${err.message}`);
  process.exit(2);
}
