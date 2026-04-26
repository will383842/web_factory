/**
 * Conventional Commits configuration for WebFactory.
 * Enforced by .husky/commit-msg via @commitlint/cli.
 *
 * Allowed types: feat, fix, docs, style, refactor, perf, test, chore, ci, build, revert
 * Scope examples: identity, catalog, content, marketing, billing, communication,
 *                 search, analytics, ai, compliance, shared, ci, docker, deps
 */
module.exports = {
  extends: ['@commitlint/config-conventional'],
  rules: {
    'type-enum': [
      2,
      'always',
      ['feat', 'fix', 'docs', 'style', 'refactor', 'perf', 'test', 'chore', 'ci', 'build', 'revert'],
    ],
    'subject-case': [2, 'never', ['upper-case', 'pascal-case', 'start-case']],
    'subject-empty': [2, 'never'],
    'subject-full-stop': [2, 'never', '.'],
    'header-max-length': [2, 'always', 100],
    'body-leading-blank': [1, 'always'],
    'footer-leading-blank': [1, 'always'],
  },
};
