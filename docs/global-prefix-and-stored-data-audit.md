# Global Prefix and Stored-Data Audit

## Prefix policy

New externally visible WordPress identifiers use the distinctive `examenanto` prefix. Existing EAS identifiers are retained only where they are required for backward compatibility.

## PHP globals

- The plugin bootstrap now defines `EXAMENANTO_VERSION`, `EXAMENANTO_DB_VERSION`, `EXAMENANTO_PLUGIN_DIR`, and `EXAMENANTO_PLUGIN_URL`.
- Bootstrap callbacks use `examenanto_maybe_upgrade_database()` and `examenanto_initialize_plugin()`.
- Deprecated `EAS_*` constants remain as aliases so existing integrations that reference them do not break.
- The old legacy classes in `admin/` and `public/` are contained in the `Examenanto\\Legacy` namespace.
- Application classes use the `Examenanto\\...` namespace and are loaded by the Examenanto autoloader.
- The autoloader provides a compatibility alias from legacy `EAS\\...` class references to their `Examenanto\\...` equivalents, so existing integrations can transition without retaining non-prefixed namespace declarations.

## Template-local variables

- Variables passed into included view templates are local rendering data, not WordPress globals. The affected templates contain a narrowly scoped PHPCS disable for `WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound`; the adjacent comment documents the include-scope rationale. This avoids renaming every controller-to-view variable while keeping the global-prefix rule active elsewhere.

## WordPress identifiers

- Active shortcodes: `[examenanto_quiz]`, `[examenanto_attempt]`, `[examenanto_my_results]`, and `[examenanto_results]`.
- The separate legacy public test class is namespaced and is not loaded by the current application bootstrap.
- Existing `[eas_*]` shortcodes remain registered as aliases for pages already published by earlier versions.
- New admin-post hooks use `admin_post_examenanto_*`; the old `admin_post_eas_*` hooks remain registered as compatibility aliases.
- Admin forms and frontend scripts now submit the new action names. Existing nonce action strings are intentionally retained so the security checks remain compatible and consistent.
- New admin menu slugs use `examenanto-*`. Hidden legacy routes preserve old bookmarked `eas-*` admin URLs.
- Script/style handles use `examenanto-*`; localized JavaScript objects use `examenantoAdminPages` and `examenantoAudio`.
- The dashboard object-cache group is now `examenanto_dashboard`.
- The quiz capability is now `examenanto_manage_quizzes`; the previous capability is retained during compatibility transition.

## Stored data and compatibility

- The canonical database-version option is now `examenanto_db_version`. It is initialized from `eas_db_version` when upgrading an existing installation, and the legacy option is synchronized after upgrades.
- Page-ID options use `examenanto_attempt_page_id`, `examenanto_results_page_id`, and `examenanto_my_results_page_id`, with legacy fallbacks and dual writes where appropriate.
- Uninstall removes plugin version and page-reference options only. It does not delete quizzes, questions, attempts, responses, pages, or uploaded recordings.
- Existing custom table names (`eas_quizzes`, `eas_pages`, `eas_items`, `eas_page_items`, `eas_answers`, `eas_attempts`, `eas_responses`, and `eas_audio`) are deliberately unchanged. Renaming them would require a separate data migration and risks breaking existing installations.
- The existing `eas-quiz` textdomain is unchanged to preserve translation compatibility.
- The audited code does not register custom post types or taxonomies, and does not use plugin-owned post metadata, user metadata, or transients.

## Compatibility principle

Legacy identifiers are not used for newly generated routes or markup where a new Examenanto identifier is available. They remain registered or readable only to keep existing installations, published pages, bookmarks, and integrations working.
