# OpenEMR Test Coverage — OAuth2, SMART-on-FHIR, FHIR, and REST API

> **Status: private working document.** §8 catalogs gaps including security-category items that could inform an attacker before the fixes land. Keep internal until the Priority 1a implementation items (§8.1a) are resolved; the rest can become public when the maintainer signs off.

**Audience:** maintainers (human and AI). **Purpose:** reference landscape of what is tested, where, how it runs, and where the gaps are, so future contributors can target gaps without re-researching the whole surface each time.

**Base state:** post-PR #14531 (merge SHA `ff1a129a86`, 2026-10-13). All file paths are relative to the repo root. Test counts reflect the state at that merge; later landings add to the picture but shouldn't invalidate the structure of this document.

**Prioritization framework** (applied in §8):

1. **Security gaps** (highest) — implementation holes that could be exploited today, and test-coverage holes where a regression could silently flip a deny-path.
2. **Testing** — coverage expansion + layer-symmetry adds that strengthen the regression signal without changing production behavior.
3. **Spec compliance gaps** — endpoints or shapes the spec requires but OpenEMR has not implemented.
4. **Feature gaps** — new functionality beyond what the spec requires.

---

## 1. Testing frameworks and how to invoke them

OpenEMR exercises OAuth2 / SMART / FHIR / API behavior across **ten** test harnesses. Each has a dedicated role; running a change through all of them is what gives the project its coverage depth.

| Harness | Config | Where tests live | What it runs against | Fires in CI | How to invoke locally |
|---|---|---|---|---|---|
| **PHPUnit isolated** | `phpunit-isolated.xml` | `tests/Tests/Isolated/`, `tests/Rector/`, selected `tests/Tests/Unit/` subtrees | No DB, no services, no app bootstrap. Pure PHP + reflection. | `.github/workflows/isolated-tests.yml` (push + PR + nightly) | `openemr-cmd phpunit-isolated` (alias `pit`) — in-container. `composer phpunit-isolated` — on host. |
| **PHPUnit api** | `phpunit.xml` → `<testsuite name="api">` | `tests/Tests/Api/` | Full stack. Guzzle drives OAuth2 + REST through Apache/nginx. Hard-fails on missing webserver in CI (no silent skip). | `.github/workflows/test.yml` reusable, invoked from `test-all.yml` + `test-scheduled.yml` across per-matrix configs (`ci/apache_*`, `ci/nginx_*`). | `openemr-cmd api-test` (alias `at`). |
| **PHPUnit e2e** | `phpunit.xml` → `<testsuite name="e2e">` | `tests/Tests/E2e/` (email set carved into `<testsuite name="email">`) | Panther (`symfony/panther`) + Selenium Chrome, against a full stack. | Per-matrix via `test.yml` where `e2e_enabled=true`. Slower than api. | `openemr-cmd e2e-test` (alias `et`). Base trait: `tests/Tests/E2e/Base/BaseTrait.php`. |
| **PHPUnit services** | `phpunit.xml` → `<testsuite name="services">` | `tests/Tests/Services/` | DB-backed service-layer exercises. Includes `FieldRenderingSnapshotTest` + `PasswordGrantHardeningTest` (38 methods, see §4). | `test.yml` reusable. | `openemr-cmd services-test` (alias `st`). |
| **PHPUnit unit** | `phpunit.xml` → `<testsuite name="unit">` | `tests/Tests/Unit/` (certain subtrees moved to isolated side) | DB-backed. Houses `tests/Tests/Unit/FHIR/SMART/*`. | `test.yml` reusable. | `openemr-cmd unit-test` (alias `ut`). |
| **PHPUnit smart-launch** | `phpunit.xml` → `<testsuite name="smart-launch">` | `tests/Tests/SmartLaunch/` (one file: `SmartLaunchQuestionnaireTest.php`) | Dedicated compose stack (`ci/smart-launch/compose.yml`) with Selenium on same network. Panther driven against `openemr/oe-smart-questionnaire` tarball-of-master. | `.github/workflows/smart-launch-test.yml` (push + PR to master/rel-\*). Timeout 30 min. | `SMART_LAUNCH_DRY_RUN=1 ci/smart-launch/run.sh` to leave the stack up. |
| **PHPUnit certification** | `phpunit.xml` → `<testsuite name="certification">` | `tests/Tests/Certification/HIT1/{US_Core_311,G10_Certification,G9_Certification}/` | ONC G10 kit via `ci/inferno/`. OpenEMR install → patient snapshot restore → phpunit cert run. | `.github/workflows/inferno-test.yml`. Timeout 180 min. Gated on `INFERNO_PAT` secret — fork PRs skip. | Submodule `ci/inferno/onc-certification-g10-test-kit` must be initialized. `ci/inferno/run.sh`. **Known hazard** (see `project_inferno_ci_signal_swallow`): the run.sh signal-swallow bug has historically shown green while hiding Bulk Group Compartment Export + Smoking Status Observations failures. |
| **PHPUnit webui / controllers / common / validators / fixtures / ECQM / redis-sentinel / email** | `phpunit.xml` (named testsuites) | `tests/Tests/{WebUi,RestControllers,Common,…}` | DB-backed integration against web UI + controllers. | `test.yml` reusable. | Per-suite via `vendor/bin/phpunit --testsuite <name>`. |
| **Acceptance (shipped-artifact smoke)** | `tests/Acceptance/phpunit.acceptance.xml` | `tests/Acceptance/` | Panther, against **built release artifacts** (not source tree). Verifies "will this release ship?" — not per-PR deny-path coverage. | `.github/workflows/{acceptance-only,acceptance-docker,acceptance-package}.yml`. | Per-release validation, not per-PR. |
| **Inferno (ONC G10)** | `ci/inferno/` orchestration | Submodule + `tests/Tests/Certification/HIT1/` | ONC `onc-certification-g10-test-kit` + OpenEMR seeded snapshot. Terminology init, phpunit cert run, Inferno HTTP suite. | `.github/workflows/inferno-test.yml`. | `ci/inferno/run.sh`. |

**Static analysis layers** (not test harnesses but part of the signal matrix):

| Layer | Scope | Fires in CI | OAuth2-specific rules |
|---|---|---|---|
| **PHPStan** level 10 (`max`) | Whole repo | `.github/workflows/{phpstan,phpstan-baseline-diff,phpstan-types}.yml` | Custom rules in `tests/PHPStan/Rules/` registered via `.phpstan/extension.neon`. **`ForbiddenAuthorizationHeaderReadRule`** (new in #14475) blocks `$request->headers->get('Authorization'|'authorization')` outside four allowed sites: `/tests/`, `BearerTokenAuthorizationStrategy.php`, `AuthorizationController.php`, `TokenIntrospectionRestController.php`. Related: `ForbiddenRequestGlobalsRule` blocks `$_SERVER['HTTP_AUTHORIZATION']`. |
| **Semgrep** | `src/` + `interface/` + `library/`; excludes vendor/node_modules/tests | `.github/workflows/semgrep.yml` (push + PR with `--baseline-commit=BASE_SHA` for diff-only; weekly cron Monday 09:00 UTC) | `semgrep.yaml` ships three OAuth2 regression guards (#14475): **`openemr-oauth2-pathinfo-superglobal-read`** (forces `$request->getPathInfo()`), **`openemr-oauth2-cors-reflect-origin-without-allowlist`** (forces `CORSAllowedOriginRegistry`), **`openemr-oauth2-nonconstant-time-secret-compare`** (forces `hash_equals`). SARIF uploaded to GitHub code-scanning. |

**External-client smokes** — distinct from acceptance tests because they drive OpenEMR from a real third-party client codebase rather than from a test fixture:

| Smoke | Workflow | Compose stack | Client source | What it exercises |
|---|---|---|---|---|
| **oe-module-api-explorer** | `.github/workflows/api-explorer-test.yml` | `docker/development-easy` (full) | `sjpadgett/oe-module-api-explorer` main HEAD (rolling, no SHA pin) | DCR all four grant configurations; then **JWT `client_credentials`** path to `fhir/Patient` asserting `Bundle` with `total >= 1`. The other three grants (auth code, password, refresh) are registered but not driven end-to-end. |
| **oe-smart-questionnaire** | `.github/workflows/smart-launch-test.yml` + `ci/smart-launch/{compose.yml,run.sh,config.sq.php}` | Dedicated compose (`openemr` + `mysql` + `selenium` on same network) | `openemr/oe-smart-questionnaire` master tarball (rolling) | Standalone SMART launch end-to-end: browser → app launch URL → OpenEMR `/authorize` → login → patient-select → consent → callback with code → app's server-side token exchange → `Patient.read` with patient context. One phpunit method (`testStandaloneLaunchReachesAppWithPatientContext`). |

Neither external-client smoke pins the external repo. The documented trade-off (see `smart-launch-test.yml` header comment): "occasional transient red (an unrelated upstream merge breaking the smoke) vs. the maintenance tax of pin-bump review cycles."

**Snapshot / fixture harnesses** — regenerable test fixtures that compare against recorded output:

- **Twig compilation** — `tests/Tests/Isolated/Common/Twig/TwigTemplateCompilationTest.php` parses every `.twig` and verifies filter/function/test references resolve.
- **Twig render** — `tests/Tests/Isolated/Common/Twig/TwigTemplateRenderTest.php` renders registered templates and diffs against `tests/Tests/Isolated/Common/Twig/fixtures/render/`. Regenerate via `openemr-cmd update-twig-fixtures` (alias `utf`) / `composer update-twig-fixtures`.
- **Layout fields** — `tests/Tests/Services/Common/Layouts/FieldRenderingSnapshotTest.php` (DB-backed) exercises each layout-field renderer branch in `library/options.inc.php` and diffs HTML against recorded fixtures. Regenerate via `openemr-cmd update-layout-field-fixtures` (alias `ulff`) / `composer update-layout-field-fixtures`.

---

## 2. Reading guide for the per-domain inventories

Each per-domain section (§3 OAuth2, §4 SMART, §5 FHIR, §6 REST API) is structured as:

- **Surfaces** — the files/classes being tested, with a one-line summary of what they do.
- **Coverage by test layer** — isolated / unit / api / e2e / certification, with concrete file:count attribution so an AI agent can jump to the right place.
- **Attack-scenario coverage** — deny-paths explicitly locked in `tests/Tests/Api/OAuth2AttackScenarioTest.php` or sibling attack suites.
- **Known gaps** — collected into §8 (not repeated per-domain).

A test count like `tests/Tests/Api/IntrospectionTest.php (32)` means 32 `#[Test]` methods in that file.

---

## 3. OAuth2 coverage

### 3.1 Surfaces under test

- **Discovery**: `src/RestControllers/Authorization/OAuth2DiscoveryController.php` (OIDC), `src/RestControllers/SMART/SMARTConfigurationController.php` (SMART), `src/RestControllers/Authorization/OAuth2PublicJsonWebKeyController.php` (JWKS).
- **DCR**: `src/RestControllers/AuthorizationController.php::clientRegistration` (POST `/oauth2/{site}/registration`) + `::oauthClientRegistrationRead` (GET `/oauth2/{site}/client/{reg}`, RFC 7592 read).
- **Token endpoint**: `AuthorizationController.php::oauthAuthorizeToken` → `league/oauth2-server` grants with custom overrides in `src/Common/Auth/OpenIDConnect/Grant/*`:
  - `CustomAuthCodeGrant.php` (incl. SMART launch extension)
  - `CustomPasswordGrant.php`
  - `CustomClientCredentialsGrant.php`
  - `CustomRefreshTokenGrant.php`
- **Authorize + consent**: `AuthorizationController.php::oauthAuthorizationFlow` + `::userLogin` + `::scopeAuthorizeConfirm` + `::authorizeUser` + `src/RestControllers/SMART/SMARTAuthorizationController.php` for patient-select.
- **Introspection**: `src/RestControllers/TokenIntrospectionRestController.php` (POST `/oauth2/{site}/introspect`).
- **Logout** (RP-initiated): `AuthorizationController.php::userSessionLogout` (GET `/oauth2/{site}/logout`).
- **Auth strategies** (per-request bearer vs. local-session vs. unauth skip): `src/RestControllers/Authorization/{BearerTokenAuthorizationStrategy,LocalApiAuthorizationController,SkipAuthorizationStrategy}.php` + `src/RestControllers/Subscriber/AuthorizationListener.php`.
- **CORS**: `src/Common/Auth/OpenIDConnect/CORSAllowedOriginRegistry.php` + `src/RestControllers/Subscriber/CORSListener.php`.
- **Repositories (League interfaces)**: `src/Common/Auth/OpenIDConnect/Repositories/{AuthCodeRepository,AccessTokenRepository,RefreshTokenRepository,ClientRepository,ScopeRepository,UserRepository}.php`.
- **Scope entities + parsing**: `src/Common/Auth/OpenIDConnect/Entities/{ScopeEntity,ScopePermissionObject,ResourceScopeEntityList,…}.php` + `src/RestControllers/SMART/{ScopePermissionParser,ScopeConsentResolver}.php`.
- **JWT infrastructure**: `lcobucci/jwt` wrapper usage in logout + id_token + JWT-assertion flows; `src/Common/Auth/OpenIDConnect/Service/JWTClientAuthenticationService.php`.

### 3.2 Coverage by test layer

**Isolated** (`tests/Tests/Isolated/`):

| File | Count | What it locks |
|---|---|---|
| `Common/Auth/OpenIDConnect/Entities/ScopeEntityTest.php` | 17 | Scope string parsing + canonical form. |
| `Common/Auth/OpenIDConnect/Entities/ScopePermissionObjectTest.php` | 25 | Permission matrix per scope entity. |
| `Common/Auth/OpenIDConnect/Entities/ScopeDelimiterRejectionIsolatedTest.php` | 7 | Rejects pathological delimiters. |
| `Common/Auth/OpenIDConnect/Entities/ResourceScopeEntityListGrantsScopeIsolatedTest.php` | 3 | List-of-scopes behavior. |
| `Common/Auth/OpenIDConnect/Entities/ClientEntityTest.php` | — | Client entity validation. |
| `Common/Auth/OpenIDConnect/Repositories/ScopeRepositoryTest.php` | 13 | Scope-repo list + description shapes. |
| `Common/Auth/OpenIDConnect/Repositories/ScopeRepositoryDescriptionIsolatedTest.php` | 2 | Description strings. |
| `Common/Auth/OpenIDConnect/Grant/ClientGrantTypeEnforcementWiringIsolatedTest.php` | 4 | Grant-type-guard wiring in the grants. |
| `Common/Auth/OpenIDConnect/Grant/ClientGrantTypeGuardTraitIsolatedTest.php` | 4 | Guard trait semantics. |
| `Common/Auth/OpenIDConnect/ClientGrantTypePolicyIsolatedTest.php` | 6 | Grant-type policy matrix. |
| `Common/Auth/OpenIDConnect/CORSAllowedOriginRegistryTest.php` | 6 | Registry: global union + per-client gates. |
| `Common/Auth/OpenIDConnect/IdTokenSMARTResponseTest.php` | 11 | SMART-flavored id_token claims. |
| `Common/Logging/EventAuditLoggerAuthFailureTest.php` | — | Auth-failure audit entries. |
| `RestControllers/Authorization/AuthorizationControllerJwksUriValidationIsolatedTest.php` | 17 | `jwks_uri` SSRF-safe URL validation at DCR time. |
| `RestControllers/Authorization/BearerTokenAuthorizationStrategyTest.php` | 12 | Bearer strategy claim/scope matrix. |
| `RestControllers/Authorization/SkipAuthorizationStrategyTest.php` | 11 | Route-prefix comparison; locks the slash-boundary fix from PR #14475. |
| `RestControllers/Authorization/LocalApiAuthorizationControllerTest.php` | — | Session-cookie auth path. |
| `RestControllers/Authorization/CheckUserHasAccessToPatientIsolatedTest.php` | 12 | ACL check for user → patient access. |
| `RestControllers/Authorization/EhrLaunchPatientContextTest.php` | 3 | EHR launch patient-context propagation (subject under test: `AuthorizationController::processAuthorizeFlowForLaunch`). |
| `RestControllers/Authorization/OAuth2DiscoveryControllerTest.php` | 1 | OIDC discovery shape. |
| `RestControllers/Authorization/OAuth2PublicJsonWebKeyControllerTest.php` | 1 | JWKS endpoint shape. |
| `RestControllers/SMART/SMARTAuthorizationControllerTest.php` | 14 | Consent-screen flow. |
| `RestControllers/SMART/SMARTConfigurationControllerTest.php` | 5 | SMART `.well-known` shape. |
| `RestControllers/SMART/ScopeConsentResolverIsolatedTest.php` | 16 | Consent-screen scope matrix. |
| `RestControllers/SMART/ScopePermissionParserIsolatedTest.php` | 5 | Scope-string → permission-tuple parsing. |
| `RestControllers/Subscriber/CORSListenerTest.php` | 8 | Three request-mode branches (bearer / session / unauth). |
| `PHPStan/ForbiddenAuthorizationHeaderReadRuleTest.php` | 4 | Custom rule's own test harness. |
| `Tools/OAuth2/ClientCredentialsAssertionGeneratorTest.php` | 10 | JWT assertion builder helper. |

**Api** (`tests/Tests/Api/`) — drives Guzzle against a running stack:

| File | Count | What it locks |
|---|---|---|
| `OAuth2AttackScenarioTest.php` | ~39 | **Attack-scenario regression gate.** Deny-paths for DCR, token endpoint client-auth, password/client-credentials, refresh, bearer presentation, introspection, CORS, info-disclosure, auth-code single-use, Basic-auth, cross-client introspection. One method per attack. Setup captures `oauth_password_grant` global BEFORE any skip path. In CI, missing webserver = hard fail (not skip). See §3.4 for the full method list. |
| `AuthorizationGrantFlowTest.php` | 7 | authorization_code happy + 4 deny paths. Shared helpers `obtainAuthorizationCode()` + `exchangeAuthorizationCode()` extract the full flow (added in #14531). |
| `AuthorizationGrantPublicClientPkceTest.php` | 3 | PKCE path, public client. |
| `AuthorizationGrantJwtAssertionFlowTest.php` | 4 | JWT-assertion auth code flow. |
| `AuthorizationClientRegistrationNegativeTest.php` | 8 | DCR body-shape rejection paths. |
| `AuthorizeEndpointParameterValidationTest.php` | 3 | /authorize parameter validation. |
| `AuthorizationLogoutTest.php` | 11 | id_token_hint validation + redirect URI allowlist. Includes tampered-sig deny (#14531). |
| `AuthorizationLogoutFullFlowTest.php` | 1 | Full logout flow end-to-end. |
| `BearerTokenRejectionPropertiesTest.php` | 3 | Property tests: expired, revoked, scope-insufficient. |
| `RefreshTokenPropertiesTest.php` | 2 | Property tests: single-use + cross-client-rejection. |
| `IntrospectionTest.php` | 32 | Full matrix: public vs. private × access vs. refresh × with/without hint × good/bad/revoked. |
| `ClientRegistrationReadTest.php` | 5 | RFC 7592 read + atomic rotation + two-concurrent-reader deny. |
| `ClientCredentialsGrantFlowTest.php` | 1 | client_credentials happy path. |
| `ClientCredentialsGrantMissingJwtTest.php` | 2 | Missing JWT assertion. |
| `ClientCredentialsDisabledClientTest.php` | 1 | Disabled client rejects. |
| `ClientGrantTypeEnforcementTest.php` | 7 | Per-client grant-type allow-list. |
| `PasswordGrantMissingParametersTest.php` | 3 | Missing param deny-paths. |
| `PasswordGrantAdditionalNegativesTest.php` | 2 | Additional deny-paths. |
| `PasswordGrantMfaRefreshFlowTest.php` | 5 | MFA + refresh after password. |
| `PasswordGrantPatientRoleFlowTest.php` | 3 | **(new #14531)** patient-role path end-to-end (happy + wrong-email + staff-creds deny). Depends on `PortalPatientFixtureManager`. |
| `JwtClientAssertionClaimValidationTest.php` | 4 | iss/exp/iat/jti claim validation. |
| `JwtAssertionExtendedNegativesTest.php` | 4 | More JWT assertion deny-paths. |
| `JwtAssertionUnknownClientTest.php` | 1 | Unknown-client JWT rejection. |
| `JwksBoundaryNegativesTest.php` | 3 | JWKS fetch edge cases (api layer). |
| `SmartConfigurationTest.php` | 5 | SMART `.well-known` end-to-end. |
| `OAuthLoginCsrfRejectionTest.php` | 1 | CSRF token enforcement on authorize login. |
| `DisabledClientRejectionTest.php` | — | Disabled-client bearer rejection. |

**Services** (`tests/Tests/Services/`):

| File | Count | Role |
|---|---|---|
| `PasswordGrantHardeningTest.php` | 38 | Service-layer: `AuthUtils` brute-force counter semantics, MFA replay, lockout thresholds, portal vs. staff counters, admin unblock reset. Does **not** go through `/token`. |
| `AdminUnblockCounterResetTest.php` | 14 | Admin unblock flow against the counter table. |
| `TrustedUserServiceTest.php` | 2 | Trusted-user persistence. |
| `ClientCredentialsGrantMisconfigTest.php` | 3 | Misconfigured client-credentials deny-paths. |
| `ClientRepositoryValidateClientNegativesTest.php` | 3 | `ClientRepository::validateClient` deny-paths. |

**Unit** (`tests/Tests/Unit/`):

| File | Count | Role |
|---|---|---|
| `FHIR/SMART/SMARTLaunchTokenTest.php` | 8 | SMART launch token serialize/deserialize. Tampered-token rejection added in #14531. |
| `FHIR/SMART/SMARTSessionTokenContextBuilderTest.php` | 2 | SMART session token context build. |
| `FHIR/SMART/SmartLaunchControllerTest.php` | — | SMART launch controller. |
| `FHIR/SMART/ClientAdminControllerTest.php` | — | Admin screen for OAuth2 clients. |
| `FHIR/SMART/ExternalClinicalDecisionSupport/{PredictiveDSIServiceEntityTest,RouteControllerTest}.php` | — | Decision-support SMART extension. |

**Acceptance** (`tests/Acceptance/`):

| File | Count | Role |
|---|---|---|
| `OAuth/OAuth2SmokeTest.php` | 2 | API-disabled gate — default install returns 404 on `/oauth2/*`. |
| `OAuth/OAuth2ApiEnabledTest.php` | 2 | API-enabled: OIDC discovery reachable, DCR mints credentials. |
| `Api/ApiSmokeTest.php` | 1 | Full DCR → auth-code → bearer `/api/facility` round-trip against a built release artifact. |
| `Api/FhirSmokeTest.php` | 2 | FHIR discovery public without auth. |
| `ApiSmokeJwtAssertionTest.php` | 2 | JWT client_credentials path to a FHIR endpoint. |

### 3.3 Scope / role / grant-type coverage matrix

| Grant type × Client auth method | Happy path | Wrong secret | Alg-none / forged | Replay / reuse | Cross-client |
|---|---|---|---|---|---|
| `authorization_code` × `client_secret_post` | `AuthorizationGrantFlowTest.php` | #14531 | — | #14531 `testSecondExchangeOfSameCodeIsRejected` | #14531 `testTokenExchangeRejectsCodeFromDifferentClient` |
| `authorization_code` × `client_secret_basic` | **Gap (api layer)** | **Gap (api layer)** | — | — | — |
| `authorization_code` + PKCE × public client | `AuthorizationGrantPublicClientPkceTest.php` (3) | — | — | — | — |
| `authorization_code` × `private_key_jwt` | `AuthorizationGrantJwtAssertionFlowTest.php` (4) | — | — | — | — |
| `password` × `client_secret_post` | `PasswordGrantMfaRefreshFlowTest.php` (5) + `PasswordGrantPatientRoleFlowTest.php` (3, #14531) | `OAuth2AttackScenarioTest.php::testPasswordGrantRejectsWrongPassword` + variants | — | — | — |
| `password` × `client_secret_basic` | `OAuth2AttackScenarioTest.php::testPasswordGrantAcceptsClientSecretBasicAuth` (#14531) | `testPasswordGrantRejectsBasicAuthWithWrongSecret` (#14531) | — | — | — |
| `client_credentials` × `private_key_jwt` | `ClientCredentialsGrantFlowTest.php` (1) | — | `OAuth2AttackScenarioTest.php::testClientCredentialsRejectsAlgNoneAssertion` + `testClientCredentialsRejectsAssertionSignedByForeignKey` + the `JwtAssertion*` family | — | — |
| `refresh_token` | `PasswordGrantMfaRefreshFlowTest.php` refresh branches (3) + `AuthorizationGrantJwtAssertionFlowTest.php` refresh (2) | — | — | `RefreshTokenPropertiesTest.php::testRefreshTokenCannotBeReusedAfterSuccessfulExchange` | `RefreshTokenPropertiesTest.php::testRefreshTokenIssuedToClientARejectedWhenPresentedAsClientB` + `DisabledClientRejectionTest.php::testRefreshGrantRejectsDisabledClient` |

### 3.4 Attack-scenario regression gate (`OAuth2AttackScenarioTest.php`)

~39 methods, one deny-path per method, grouped as follows. Each method asserts status + error-field shape (not just "not 200"); the suite's job is to catch regressions that silently accept a previously-denied attack.

| Section | Methods |
|---|---|
| **DCR** | `testDcrRejectsNonUrlRedirectUri`, `testDcrRejectsEmptyRedirectUrisArray`, `testDcrRejectsRelativeRedirectUri`, `testDcrRejectsWebSchemeWithoutHost`, `testDcrRejectsNonWebSchemeWithoutDot`, `testDcrAcceptsRfc8252PrivateUseSchemeRedirectUri`, `testDcrRejectsUnsupportedTokenEndpointAuthMethod`, `testDcrResponseContainsNoPrivateKeyMaterial` |
| **Token endpoint — client auth** | `testTokenEndpointRejectsUnregisteredClient`, `testTokenEndpointRejectsWrongClientSecret`, `testTokenEndpointRejectsAlgNoneAssertion`, `testTokenEndpointRejectsAssertionSignedByForeignKey`, `testPasswordGrantAcceptsClientSecretBasicAuth` (#14531 happy), `testPasswordGrantRejectsBasicAuthWithWrongSecret` (#14531) |
| **Password grant** | `testPasswordGrantRejectsWrongPassword`, `testPasswordGrantRejectsUnknownUser`, `testPasswordGrantRejectsWrongUserRole` |
| **client_credentials** | `testClientCredentialsRejectsAlgNoneAssertion`, `testClientCredentialsRejectsAssertionSignedByForeignKey` |
| **Refresh** | `testRefreshWithMadeUpTokenIsRejected` |
| **Bearer presentation on FHIR** | `testFhirRejectsAuthorizationBasicScheme`, `testFhirRejectsBearerAlgNoneJwt`, `testFhirRejectsBearerSignedByForeignKey`, `testFhirRejectsBearerMalformedJwt`, `testFhirRejectsEmptyAuthorizationHeader`, `testFhirRejectsMissingAuthorizationHeader` |
| **Introspection** | `testIntrospectionTreatsMadeUpTokenAsInactive`, `testIntrospectionWithoutClientIdReturnsInactive`, `testIntrospectionReportsInactiveWhenAuthenticatingClientDidNotMintTheToken` (#14531 cross-client) |
| **CORS** | `testCorsPreflightFromDisallowedOriginEmitsNoAllowOriginHeader`, `testCorsResponseFromDisallowedOriginEmitsNoAllowOriginHeader`, `testCorsResponseEmitsAllowOriginForBearerAuthFromTokensOwnClientRegisteredOrigin` (#14527), `testCorsResponseDoesNotEmitAllowOriginForOriginRegisteredUnderDifferentClient` (#14527), `testCorsResponseDoesNotEmitAllowOriginForRegisteredOriginOnUnauthenticatedRequest` (#14527), `testCorsResponseMatchesAnyOfClientsMultipleRegisteredOrigins` (#14527), `testCorsResponseDoesNotEmitAllowOriginWhenClientIsDisabled` (#14527) |
| **Info disclosure** | `testDiscoveryEndpointAdvertisesOnlyPublicEndpoints`, `testJwkEndpointPublishesOnlyPublicKeys`, `testVersionEndpointRequiresBearerToken`, `testProductEndpointRequiresBearerToken` |

**Shared helpers in the attack suite** (reused by future deny-path tests):

- `dcr(overrides)` / `dcrFull(tag, overrides)` / `dcrConfidentialPasswordGrantClient(tag, overrides)` — DCR + optional admin-approve flip. All accept DCR body overrides.
- `postTokenRaw(form)` / `postTokenRawWithBasicAuth(clientId, secret, form)` / `rawPostForm(path, form)` / `rawGet(path, headers)` / `rawOptions(path, headers)`.
- `passwordGrantForTest(clientId, secret, scope)` — asserts non-empty `access_token`.
- `assertRejected(response, context)` — 4xx + JSON body with `error` field.

### 3.5 Repository seams

- **`AuthCodeRepository`** (`src/Common/Auth/OpenIDConnect/Repositories/AuthCodeRepository.php`): post-#14531 persists revocation state in `oauth_auth_code_revocations` (`code_id` PK, `revoked_at`). `isAuthCodeRevoked` does the atomic claim INSERT; duplicate-key = revoked. `revokeAuthCode` is a no-op + 1-hour opportunistic prune. See **§8 known gap** on validation-order DoS trade-off.
- **`ScopeRepository`** (same dir): defines the full scope list — `system/Patient.$export`, `system/Group.$export`, `system/*.$bulkdata-status`, SMART `launch`, `launch/patient`, every US Core patient/user/system resource scope. Used by League's grants + consent screen.
- **`ClientRepository`**: enabled-flag + grant_types + jwks + jwks_uri. #14475 moved to `hash_equals` on secret compares. The grant_types column was backfilled in `sql/8_4_1-to-8_5_0_upgrade.sql`.

### 3.6 CORS specifics (#14527)

- **Preflight** (`CORSListener::onKernelRequest` priority 25) — stays lenient against the global union. CORS forbids credentials on preflight, so `clientId` is not known. The real gate is response-side.
- **Response** (`onKernelResponse` priority 0) — three request modes:
  - **Bearer-auth** (`clientId` string from `BearerTokenAuthorizationStrategy`): emit `Allow-Origin` only if Origin matches **that specific client's** registered redirect_uri origins (`CORSAllowedOriginRegistry::isAllowedForClient`).
  - **Session-cookie auth** (`clientId=null` from `LocalApiAuthorizationController`): no `Allow-Origin` — same-origin only.
  - **Unauth** (no `clientId` attribute): no `Allow-Origin` — attacker JS can't read the 401 body.
- `Vary: Origin` is set unconditionally on any response with an Origin header.

---

## 4. SMART-on-FHIR coverage

### 4.1 Surfaces under test

- **Launch token**: `src/FHIR/SMART/SMARTLaunchToken.php` — encrypted-blob serialize/deserialize for the `launch` parameter.
- **Launch controller**: `src/RestControllers/SMART/SMARTAuthorizationController.php` — consent + patient-select UI.
- **Patient context propagation**: `AuthorizationController::processAuthorizeFlowForLaunch` (private; exercised via `EhrLaunchPatientContextTest`).
- **Session context builder**: `src/Common/Auth/OpenIDConnect/SMARTSessionTokenContextBuilder.php`.
- **Launch admin UI**: `src/FHIR/SMART/SMARTLaunchController.php`.
- **Discovery**: `src/RestControllers/SMART/SMARTConfigurationController.php` — `/.well-known/smart-configuration`.
- **ACL for patient access**: `src/RestControllers/Authorization/CheckUserHasAccessToPatient.php`.

### 4.2 Coverage by test layer

| Layer / file | Count | What it locks |
|---|---|---|
| `tests/Tests/Unit/FHIR/SMART/SMARTLaunchTokenTest.php` | 8 | Constructor + serialize/deserialize + tampering rejection (#14531). |
| `tests/Tests/Unit/FHIR/SMART/SMARTSessionTokenContextBuilderTest.php` | 2 | Context-builder output shape. |
| `tests/Tests/Isolated/RestControllers/Authorization/EhrLaunchPatientContextTest.php` | 3 | Launch-token → session puuid propagation. |
| `tests/Tests/Isolated/RestControllers/Authorization/CheckUserHasAccessToPatientIsolatedTest.php` | 12 | User × patient ACL matrix. |
| `tests/Tests/Isolated/RestControllers/SMART/SMARTAuthorizationControllerTest.php` | 14 | Consent screen flow. |
| `tests/Tests/Isolated/RestControllers/SMART/SMARTConfigurationControllerTest.php` | 5 | SMART `.well-known` shape. |
| `tests/Tests/Isolated/RestControllers/SMART/ScopeConsentResolverIsolatedTest.php` | 16 | Scope → consent-screen resolution. |
| `tests/Tests/Isolated/RestControllers/SMART/ScopePermissionParserIsolatedTest.php` | 5 | Scope string parsing. |
| `tests/Tests/Isolated/Common/Auth/OpenIDConnect/IdTokenSMARTResponseTest.php` | 11 | SMART-shaped id_token claims. |
| `tests/Tests/Isolated/FHIR/SMART/ClientAdminWriteAuditTest.php` | 2 | Audit log on client admin writes. |
| `tests/Tests/Isolated/FHIR/SMART/ResourceConstraintFiltererTest.php` | 10 | `ResourceConstraintFilterer` scope-constraint matrix. |
| `tests/Tests/Isolated/FHIR/SMART/ScopeConstraintEnforcementWiringIsolatedTest.php` | 2 | Verifies `FhirDocumentReferenceRestController` wires in the filterer. |
| `tests/Tests/Api/SmartConfigurationTest.php` | 5 | End-to-end SMART discovery. |
| `tests/Tests/SmartLaunch/SmartLaunchQuestionnaireTest.php` | 1 | **End-to-end standalone SMART launch** against `openemr/oe-smart-questionnaire`. Panther-driven. |

### 4.3 Standalone vs. EHR launch

- **Standalone launch** — covered end-to-end by the `smart-launch-test.yml` smoke driving `oe-smart-questionnaire`.
- **EHR launch** — `EhrLaunchPatientContextTest` covers the private propagation method, but **no end-to-end browser flow** exercises the "user inside OpenEMR clicks a SMART app menu entry → `launch=<launch_param>` → authorize" shape.

### 4.4 ONC G10 / US Core coverage

Via `tests/Tests/Certification/HIT1/`:

- `US_Core_311/InfernoSinglePatientAPITest.php` — **31 methods** covering CapabilityStatement + every US Core resource. See §5.4 for the per-resource list.
- `G10_Certification/SinglePatient311APITest.php` — 2 (`testSingleApiRunWithV1Scopes`, `testSingleApiRunWithV2Scopes`).
- `G10_Certification/SinglePatient700APITest.php` — 1.
- `G10_Certification/BulkPatientExport311APITest.php` — 1.
- `G9_Certification/CCDADocRefGenerationTest.php` — 1.

Driven by `ci/inferno/run.sh` with `ci/inferno/test_configs/standalone-full-access.yml` (symmetric confidential client, PKCE S256, scope union covering every US Core resource + `launch/patient` + `offline_access`).

**Known hazard**: see `project_inferno_ci_signal_swallow` — the run.sh signal-swallow bug has historically shown green while hiding failures. Treat Inferno green with mild skepticism until that bug is fixed.

### 4.5 External-client E2E

`openemr/oe-smart-questionnaire` smoke via `.github/workflows/smart-launch-test.yml`:

- **Mode**: standalone SMART launch (not EHR launch).
- **Client type**: `symmetric` (confidential) with PKCE S256.
- **Scope requested**: `launch/patient openid fhirUser offline_access patient/*.read`.
- **Flow**: browser → `https://openemr/smart-questionnaire/launch.php` → OpenEMR `/authorize` → admin login → patient-select (demo patient from `post_install_upgrade` seeding) → consent → callback with code → app's server-side token exchange → `Patient.read` with patient context → assertion against rendered HTML.
- **DCR path**: app's own `bin/php bin/register.php --all` CLI.
- **Enabled by**: direct SQL flip of `is_enabled=1` on `oauth_clients` matching `client_name LIKE 'OpenEMR SMART Questionnaire smoke%'`.

Not pinned to a SHA — pulls upstream master tarball.

---

## 5. FHIR coverage

### 5.1 Surfaces under test

- **CapabilityStatement**: `src/RestControllers/RestControllerHelper.php::getCapabilityStatement` + `src/RestControllers/FHIR/FhirMetaDataRestController.php`.
- **US Core R4 resources**: 123 routes in `apis/routes/_rest_routes_fhir_r4_us_core_3_1_0.inc.php` — per-resource `src/RestControllers/FHIR/Fhir*RestController.php` + service-layer `src/Services/FHIR/Fhir*Service.php`.
- **Bulk Data IG**: `src/RestControllers/FHIR/BulkFhirRestController.php`, `src/FHIR/Export/ExportJob.php`, OperationDefinition at `src/RestControllers/FHIR/Operations/FhirOperationDefinitionRestController.php` (routes `/fhir/OperationDefinition` + `/fhir/OperationDefinition/{op}`).
- **Scope-constrained reads**: `src/RestControllers/FHIR/FhirDocumentReferenceRestController.php` uses `src/Common/Auth/OpenIDConnect/Entities/ResourceConstraintFilterer.php` + `src/FHIR/SMART/ScopeConstraintFilterer.php`.
- **Patient compartment**: `src/Services/FHIR/UsersRowPatientAllowlist.php`, `src/Services/FHIR/PractitionerAttributionPolicy.php`.

### 5.2 CapabilityStatement + discovery

| Layer / file | Count | What it locks |
|---|---|---|
| `tests/Tests/Api/CapabilityFhirTest.php` | 3 | `testInvalidPathGet`, `testInvalidSiteGet`, `testGet` — shape against running `/apis/default/fhir/metadata`. |
| `tests/Tests/Isolated/RestControllers/RestControllerHelperCapabilityTest.php` | — | Isolated capability-building helper. |
| `tests/Acceptance/Api/FhirSmokeTest.php` | 2 | `/metadata` + `.well-known/smart-configuration` reachable without auth per spec. |
| `tests/Tests/Certification/HIT1/G10_Certification/SinglePatientApi/CapabilityStatementTest.php` | — | Via `SinglePatientApiTestTrait`. |

### 5.3 US Core resource per-resource api tests

Convention: `Fhir<Resource>ApiTest.php` for read-side (older) + `Fhir<Resource>FhirWriteApiTest.php` for write-side (newer).

| Resource | Read test file (count) | Write test file (count) |
|---|---|---|
| AllergyIntolerance | `AllergyIntoleranceFhirApiTest.php` (1) | `AllergyIntoleranceFhirWriteApiTest.php` (3) |
| Appointment | — | `AppointmentFhirWriteApiTest.php` (2) |
| CarePlan | — | `CarePlanFhirWriteApiTest.php` (3) |
| CareTeam | — | `CareTeamFhirWriteApiTest.php` (3) |
| Condition | `ConditionFhirApiTest.php` (8) | `ConditionFhirWriteApiTest.php` (3) |
| Coverage | — | `CoverageFhirWriteApiTest.php` (3) |
| Device | — | `DeviceFhirWriteApiTest.php` (3) |
| Encounter | — | `EncounterFhirWriteApiTest.php` (3) |
| Goal | — | `GoalFhirWriteApiTest.php` (3) |
| Immunization | — | `ImmunizationFhirWriteApiTest.php` (3) |
| Medication | — | `MedicationFhirWriteApiTest.php` (3) |
| MedicationDispense | `MedicationDispenseFhirApiTest.php` (8) | — |
| MedicationRequest | — | `MedicationRequestFhirWriteApiTest.php` (3) |
| Observation | — | `ObservationFhirWriteApiTest.php` (4) |
| Organization | `OrganizationFhirApiTest.php` (12) | — |
| Patient | `PatientFhirApiTest.php` (9) | — |
| Person | — | `PersonFhirWriteApiTest.php` (3) |
| PractitionerRole | — | `PractitionerRoleFhirWriteApiTest.php` (3) |
| Provenance | `ProvenanceFhirApiTest.php` (1) | — |
| Questionnaire | — | `QuestionnaireFhirWriteApiTest.php` (3) |
| QuestionnaireResponse | — | `QuestionnaireResponseFhirWriteApiTest.php` (3) |
| RelatedPerson | — | `RelatedPersonFhirWriteApiTest.php` (4) |
| ServiceRequest | — | `ServiceRequestFhirWriteApiTest.php` (3) |

Service-layer coverage adds ~25 `Fhir<Resource>ServiceCrudTest.php` + `*QueryTest.php` for Patient + AllergyIntolerance + per-US-Core-8-compliance tests for CareTeam / MedicationDispense / MedicationRequest / Patient (`FhirCareTeamServiceUSCore8Test.php`, `FhirMedicationDispenseUSCore8ComplianceTest.php`, etc.). Observation sub-subtypes live under `tests/Tests/Services/FHIR/Observation/` (5 files) + `tests/Tests/Isolated/Services/FHIR/Observation/` (2).

### 5.4 ONC US Core coverage via Inferno

`tests/Tests/Certification/HIT1/US_Core_311/InfernoSinglePatientAPITest.php` — 31 methods, one per resource/profile:

CapabilityStatement, Patient, AllergyIntolerance, CarePlan, Condition, Device, DiagnosticReportNote, DiagnosticReportLab, DocumentReference, Goal, Immunization, MedicationRequest, SmokingStatus, PediatricWeightForHeight, ObservationLab, PediatricBmiForAge, PulseOximetry, HeadCircumference, BodyHeight, BodyTemp, BloodPressure, BodyWeight, HeartRate, RespRate, Procedure, Encounter, Organization, Practitioner, ClinicalNotesGuidance, DataAbsentReason, Provenance.

### 5.5 Bulk Data IG

- `tests/Tests/Api/GroupExportFhirApiTest.php` — **1 method**: `testGroupExportWithNonExistingGroupId`. Negative-only.
- `tests/Tests/Certification/HIT1/G10_Certification/BulkPatientExport311APITest.php` — 1 (`testBulkPatientExport`).
- `tests/Tests/Isolated/FHIR/Export/ExportJobTest.php` + `ExportMemoryStreamWriterTest.php` — service-layer ExportJob behavior.
- `tests/Tests/Services/FHIR/Export/ExportStreamWriterCollectorTest.php` + related — stream-writer collector behavior.

### 5.6 Patient-compartment + ACL isolation

- `tests/Tests/Isolated/FHIR/FhirServiceBaseFailClosedIsolatedTest.php` — fail-closed base behavior.
- `tests/Tests/Isolated/FHIR/FhirMediaServicePatientCompartmentIsolatedTest.php`
- `tests/Tests/Isolated/FHIR/FhirPersonServiceNonPatientCompartmentIsolatedTest.php`
- `tests/Tests/Isolated/FHIR/FhirQuestionnaireResponseServicePatientCompartmentIsolatedTest.php`
- `tests/Tests/Isolated/Services/FHIR/UsersRowPatientAllowlistIsolatedTest.php`
- `tests/Tests/Isolated/Services/FHIR/PractitionerAttributionPolicyIsolatedTest.php`
- `tests/Tests/Isolated/Services/FHIR/FhirPractitionerServicePatientCallerViewIsolatedTest.php`
- `tests/Tests/Isolated/RestRoutes/FhirRouteAclEnforcementIsolatedTest.php` — 13 methods on route-level ACL enforcement.

---

## 6. REST API coverage

### 6.1 Route catalog

Routes aggregated in `_rest_routes.inc.php` from three sources:

| Route map | Count | Prefix |
|---|---|---|
| `apis/routes/_rest_routes_standard.inc.php` | 96 | `/apis/default/api/*` |
| `apis/routes/_rest_routes_fhir_r4_us_core_3_1_0.inc.php` | 123 | `/apis/default/fhir/*` |
| `apis/routes/_rest_routes_portal.inc.php` | 5 | `/apis/default/portal/*` |

### 6.2 /apis/default/api/* coverage

Non-FHIR api tests in `tests/Tests/Api/` (as of master `c5d09ed9eb`, 2026-10-21):

| File | Count | Notes |
|---|---|---|
| `AppointmentApiTest.php` | 8 | DELETE added by PR #14680; patient-compartment GET list + GET single added by PR #14729; wrong-patient regression gate added by PR #14734 |
| `BackgroundServiceApiTest.php` | 6 | NEW via PR #14749 (also shipped scope registry + `/run` → `/$run` route rename; see §8.3 latent-bug register for the 7-month-dormant OAuth2 reachability bug it closed) |
| `DentalIssueApiTest.php` | 4 | NEW via PR #14681 |
| `DocumentApiTest.php` | 15 | |
| `DrugApiTest.php` | 2 | NEW via PR #14675 |
| `EncounterApiTest.php` | 4 | NEW via PR #14722 |
| `FacilityApiTest.php` | 7 | |
| `HealthEndpointTest.php` | 3 | |
| `ImmunizationApiTest.php` | 2 | NEW via PR #14675 |
| `InsuranceCompanyApiTest.php` | 10 | GET list + single-success + 3 PATCH gates (PR #14680 + PR #14688) |
| `InsuranceEmployerSearchApiTest.php` | 9 | |
| `InsuranceTypeApiTest.php` | 1 | NEW via PR #14729 |
| `ListApiTest.php` | 2 | |
| `ListRestControllerEmptyListTest.php` | 1 (× 3 data-provider rows) | NEW via PR #14681 |
| `MedicationApiTest.php` | 4 | NEW via PR #14680 |
| `MessageApiTest.php` | 3 | NEW via PR #14729 |
| `PatientAllergyConditionApiTest.php` | 21 | d+e+f coverage expansion (allergy + medical_problem CRUD + top-level reads) added by PR #14733; wrong-patient + unknown-puuid PUT regression gates added by PR #14747 |
| `PatientApiTest.php` | 9 | employer GET + 400-shape added by PR #14729 |
| `PatientInsuranceApiTest.php` | 9 | NEW via PR #14728; swap-insurance operation coverage (4 tests) added by PR #14732; wrong-patient PUT regression gate added by PR #14747 |
| `PatientTransactionApiTest.php` | 4 | NEW via PR #14689; empty-list regression gate added by PR #14727 |
| `PractitionerApiTest.php` | 7 | |
| `PrescriptionApiTest.php` | 9 | |
| `ProcedureApiTest.php` | 2 | NEW via PR #14720 (also shipped the ProcedureService SQL fix) |
| `ProductApiTest.php` | 1 | |
| `SoapNoteApiTest.php` | 4 | NEW via PR #14722 |
| `SurgeryApiTest.php` | 4 | NEW via PR #14681 |
| `UserApiTest.php` | 3 | NEW via PR #14721 |
| `VersionApiTest.php` | 1 | |
| `VitalApiTest.php` | 5 | NEW via PR #14689 |

**Expansion roadmap (as of 2026-10-20).** The 96 standard routes vs. the file count above still leaves some route patterns without a dedicated api-tier test file. Rough plan:

- **Batch 1 (PR #14675, MERGED 2026-10-19 at `6c352d57c6`):** `/api/drug` + `/api/immunization` — 2 new resource files, 4 tests, 24 assertions. Also carries a prod fix in `ViewRendererListener` (rabbit-caught): `http_response_code()` was being dropped when wrapping controller returns into `JsonResponse` / `Response`, so every `handleProcessingResult(…, 4xx)` call site silently returned 200. Fix: capture + thread the status through with an `is_int` narrow for the CLI-default `false`. 12+ call sites across controllers were affected (now correctly 4xx). Shipped alongside: `#[OA\Response]` 404 declarations on Drug + Immunization + regenerated `swagger/openemr-api.yaml`; new isolated `ViewRendererListenerTest` (3 tests, 10 assertions) covering JSON + text branches with `#[RunInSeparateProcess]` on the fallback-200 test to actually exercise the `is_int` guard; data-provider `@codeCoverageIgnore` ride-along nitpick from the just-landed #14653. **Scope narrowed from the original 4 resources** — the two deferrals (`/api/procedure` SQL fix, `/api/user` coverage) both shipped later as PR #14720 and PR #14721; see the "Deferred from batch 1" entries above the roadmap for details. Zero conflict with #14653.
- **Batch 2 (scope collapsed 2026-10-19 after re-audit):** The original batch 2 target — "backfill single-GET + PUT on existing-covered resources (facility, practitioner, patient, prescription + DELETE)" — turned out to be **already covered** when the existing test files were re-inspected. FacilityApiTest, PractitionerApiTest, PatientApiTest, PrescriptionApiTest each already have a full CRUD + 400-shape matrix. The §6.2 roadmap bullet was written against a stale snapshot. **The residual batch 2 gap collapsed to:** Appointment POST + DELETE (`/api/patient/:pid/appointment` POST + `/api/patient/:pid/appointment/:eid` DELETE) + InsuranceCompany GET list + GET single-success + PUT. The InsuranceCompany single-GET + PUT routes overlap with batch 3 (they're exactly the ones #14653 fixed), so the honest consolidated move is batch 2+3 as one PR.
- **Batch 2+3 slice (PR #14680, MERGED 2026-10-19 at `9983b59310`):** Appointment DELETE + InsuranceCompany GET list + GET single-success + Medication CRUD (full `/api/patient/:pid/medication/*` surface). 16 tests, 96 assertions, 3 files (2 extended + 1 new). Locks PR #14653's ListService fixes (insert/getOne/getAll uuid handling) and InsuranceCompanyService::getOneById stringify fix at the api tier. **Mid-PR CI fix:** `testDeleteRemovesMedication` was failing on every lane because `ListRestController::getAll` returns 404 for an empty list (same empty-array-as-404 bug `AppointmentRestController` had pre-#14605). drid in CI doesn't seed any other medications for pid=1, so after DELETE the list is empty → 404. Switched the gone-check from list-GET to single-GET-returning-404; works regardless of other medications on the patient. **InsuranceCompany PUT dropped from scope** — service-tier test from #14653 covers that path and api-tier would need a full-field payload the service doesn't support cleanly. Four rabbit nits applied (fixture-row uuid assertion, tightened UUID regex, match-by-id-not-title). **Latent bug surfaced:** `ListRestController::getAll`'s empty-array-as-404 is the same shape #14605 fixed for appointments — worth a follow-up PR to apply the same pattern to ListRestController.
- **Batch 2+3 remainder + ListRestController fix (PR #14681, MERGED 2026-10-19 at `7dc1525903`):** Surgery CRUD + Dental_issue CRUD (identical ListService shape as medication) + `ListRestController::getAll` empty-array-as-404 fix mirroring PR #14605's `AppointmentRestController::listResponse()` pattern + dedicated empty-list regression gate (`ListRestControllerEmptyListTest`) parameterised across the three resource types. 11 tests, 89 assertions, 4 files (3 new + 1 modified). CoderabbitAI: "No actionable comments" (clean review).
- **Vital + Patient transaction CRUD coverage (PR #14689, MERGED 2026-10-20 at `9308c3183b`):** Closes the two items deferred from batch 2+3 remainder. `VitalApiTest` NEW (5 tests on `/api/patient/:pid/encounter/:eid/vital/*` including a cross-patient IDOR 404 gate; **also locks PR #14653's `VitalsService::update` preserve-uuid-on-update invariant at the api tier**) + `PatientTransactionApiTest` NEW (3 tests on `/api/patient/:pid/transaction` + `/api/transaction/:tid` POST/GET/PUT, **locking PR #14653's `PatientTransactionService::insert` both-ids return shape at the api tier**). `ApiTestClient::ALL_SCOPES` extended with `user/transaction.read` + `user/transaction.write` (the transaction routes authorize on CRUDS which the test client's token did not previously carry). Rabbit followup: `testGetAll` tightened from "data non-empty" to body-match via `collectRows`; `VitalApiTest::tearDown` adds `FixtureManager::removeObservationFixtures()` to sweep leaked `form_vitals` + `forms` + uuid_registry/mapping rows (`EncounterFixtureManager::removeFixtures()` only deletes encounter + patient + facility).
- **InsuranceCompanyService::update PATCH + address merge/insert (PR #14688, MERGED 2026-10-20 at `dc083555e6`):** Prod fix landed alongside #14689. The service's `update()` now fetches the stored row via `getOneById()` and overlays the submitted payload (true semantic PATCH); unmentioned columns keep their value, clients can clear with explicit `null`. **Rabbit review surfaced two additional defects**: (1) `AddressData::fromArray($data)` was substituting empty strings for missing keys, so a payload with only `line1` got silently dropped by the city+state guard, and a payload with only `city`+`state` wiped the stored `line1` — fixed by merging submitted address keys over `addressService->getOneByForeignId()` via `array_intersect_key`. (2) When a company was POSTed without city+state (no addresses row), the subsequent PUT with address fields failed with 404 because `addressService->update()` returned null — fixed by falling through to `addressService->insert()`. Three api-tier regression gates in `InsuranceCompanyApiTest`. Also tightened `InsuranceCompanyService::$addressService` to `private readonly AddressService` (dropped two baseline entries). **Known limitation:** both new tests include `name` in the PATCH payload because the controller still runs `DATABASE_INSERT_CONTEXT` validation on PUT (requires name) — widening to `DATABASE_UPDATE_CONTEXT` is a separate concern (that context requires `uuid` which the REST route doesn't carry since the route uses the numeric `iid`).
- **Batch 4 slice 1 (PR #14722, MERGED 2026-10-20 at `a4e9db684b`):** Encounter CRUD (uuid-based) + SOAP note CRUD (int-based sub-routes under encounter). 2 new api test files, 8 tests. `EncounterApiTest` uses `EncounterFixtureManager` + joins `form_encounter -> patient_data` in setUp to pick the specific puuid/euuid pair (fixture seeds 13 patients but only one encounter). `SoapNoteApiTest` adds explicit `forms` sweep for formdir='soap' (distinct from Encounter's formdir='newpatient'; rabbit flagged my first attempt that applied the wrong formdir to SOAP). 2 rabbit followup rounds applied: form-sweep correction + Encounter forms-row cleanup, then objective/assessment assertions on SOAP PUT.
- **Batch 4 slice 2 (PR #14728, MERGED 2026-10-20 at `4430b57659`):** patient-compartment insurance CRUD (`/api/patient/:puuid/insurance/*` — 4 routes). Uses existing `FixtureManager::installPatientFixtures()` + `installInsuranceCompanyFixture()` (did not need a new fixture manager after all — the existing helpers chain cleanly). 4 tests, 48 assertions. tearDown removes coverage rows first, then insurance_companies, then patient_data in dependency order, wrapped in try/finally so OAuth client cleanup runs on failure. Non-obvious shape: the POST returns 200 (not 201) with a single-row data shape; `CoverageValidator` requires both `date` (effective-start) AND `date_end` (nullable).
- **TransactionRestController empty-array-as-404 fix (PR #14727, MERGED 2026-10-20 at `03ba739d7a`):** Prod fix shipped alongside slice 2. Same bug family as PR #14605 (AppointmentRestController) and PR #14681 (ListRestController) — `GetPatientTransactions` was returning 404 for a transaction-less patient instead of 200 + `data: []`. Removed the empty-array 404 branch; added `testGetAllForPatientWithNoTransactionsReturnsEmptyList` in `PatientTransactionApiTest` as the regression gate using a test-unique pubpid (`test-txn-empty-{uniqid}`) direct SQL insert + DELETE, not `FixtureManager::installPatientFixtures()` — the shared `test-fixture-%` prefix sweep would touch concurrent tests' patients. Baseline updates: dropped 1 `count(mixed)` entry + reduced `handleProcessingResult-deprecated` count 4 → 3.
- **Audit-PR a+b+c combined (PR #14729, MERGED 2026-10-20 at `69eb1e5ff5`):** Post-batch route audit closed its three smallest-slice items in one PR — `insurance_type` (1 route, new `InsuranceTypeApiTest.php`) + patient-employer (1 route, extends `PatientApiTest` with `testGetEmployer` + `testGetEmployerWithInvalidUuidReturns400`) + patient-compartment Appointment GET list + GET single (2 routes, extends `AppointmentApiTest`) + Message CRUD (3 routes, new `MessageApiTest.php`). 7 routes, 8 tests across 4 files. Non-obvious shape: `AppointmentRestController` returns `pc_eid` as **int** in the patient-compartment list route but as **string** in the top-level `/api/appointment` list — numeric narrowing before `assertContains`. `MessageService::delete` flips `pnotes.deleted = 1` (soft delete), not `activity = 0`. Rabbit nit followup (merge `f04d0fa077`): `testGetAppointmentListForPatient` now POSTs a second appointment for another fixture patient and asserts its eid is NOT in the pid-scoped response, so a regression that drops the WHERE pid filter actually trips. **Deferred within audit-PR a:** `GET /api/patient/:puuid/insurance/$swap-insurance` — the handler behavior needs behavioral exploration before locking a shape; moved to audit-PR h in the gap register.
- **Audit-PR h — swap-insurance operation coverage (PR #14732, MERGED 2026-10-21 at `6e75f51c7091`):** 4 new api tests extending `PatientInsuranceApiTest` for `GET /api/patient/{puuid}/insurance/$swap-insurance` — two-policy positive swap, single-policy positive swap, unknown insurance uuid (400), invalid patient uuid (400). Also added `user/insurance.$swap-insurance` to `ApiTestClient::ALL_SCOPES` — surfaced that the swap operation uses its own ONC-shaped operation-scope (via `ServerScopeListEntity::createUsersScopes`), separate from `user/insurance.write`, which is why the op was unreachable from the api-tier before. Rabbit nit followup (merge `5b31e238f0`): `assertArrayHasKey('puuid::INVALID_PUUID', ...)` on the invalid-puuid negative so a response that only fails on the unknown-insurance side cannot silently pass.
- **Audit-PR d+e+f — allergy + medical_problem CRUD + top-level reads (PR #14733, MERGED 2026-10-21 at `7c2b21fe7de4`):** 12 new tests across the three slices, all added to the existing `PatientAllergyConditionApiTest.php` (brings file to 17 methods / 206 assertions). d: `testPostAllergyCreatesRecord` + `testPutAllergyUpdatesRecord` + `testDeleteAllergyRemovesRecord`. e: same shape for `medical_problem`. f: `testGet{Allergy,MedicalProblem}TopLevel{List,Single,SingleNotFound}` — six tests covering the top-level `/api/allergy` + `/api/medical_problem` list + single routes. Rabbit nit followup (merge `a9325c7ce5`): `assertArrayHasKey("id", $body["data"])` on both allergy + medical_problem POST responses — the create payload includes both `id` and `uuid` and the original test asserted only `uuid`. **Rabbit's 2nd-round nit declined:** attempted to tighten the post-delete follow-up GET with `assertEquals(200, ...)` + `assertArrayNotHasKey("internalErrors")` but the actual behavior is 400 — `AllergyIntoleranceService::delete` hard-removes the uuid from `uuid_registry`, so the subsequent `TokenSearchField(..., isUuid=true)` lookup fails as a validation error. The original `assertSame([], data)` is weak against error-vs-empty masquerading but there's no clean tightening to 200 or 404 that reflects actual behavior; worth a service-layer refactor as a separate concern. **Audit-PR g (background_service) split off** — needs OAuth2 scope-registry work (`user/background_service.s/.r/.c` not in `ServerScopeListEntity::apiScopes()`); non-trivial prod change rather than just test wiring.
- **Appointment pid-scoping fix (PR #14734, MERGED 2026-10-21 at `fc1e82bb190e`):** Closes the §8.3 latent-bug entry — route handler for `GET /api/patient/:pid/appointment/:eid` was passing only `$eid` to `AppointmentRestController::getOne`, discarding the `$pid` path segment. New controller method `getOneScopedByPatient(string $pid, string $eid): Response` mirrors `delete()`'s pattern: fetches via `AppointmentService::getAppointment($eid)` and validates against the raw array before the response is built (not by parsing a serialized `Response` body — rabbit's observation that the first-cut JSON-body-parse approach would silently 404 every valid request if the payload wrapper ever changed). Route closure now typed `string $pid, string $eid` and calls the new method directly. OpenAPI attribute moved off a stale location on `getOneForPatient()` (which only serves the portal UUID route) onto the new method; swagger regenerated. Regression gate `testGetAppointmentByIdForPatientRejectsWrongPatient` added to `AppointmentApiTest`. 4 commits total (initial fix + refactor + swagger regen).

- **Patient-compartment PUT pid-scoping sweep (PR #14747, MERGED 2026-10-21 at `5a6fd28704c2`):** Systematic audit after #14734 covering every `/patient/:pid/*` and `/patient/:puuid/*` route in both standard + portal route files. Three PUT routes had a different flavor of the pid-discard pattern — the controller accepted the path puuid but either (a) didn't resolve + pass it to the service, or (b) resolved it conditionally and let the service's compartment check silently skip when the resolution failed. Fixed: `PUT /patient/:puuid/allergy/:auuid` + `PUT /patient/:puuid/medical_problem/:muuid` + `PUT /patient/:puuid/insurance/:insuranceUuid`. 3 wrong-pid regression gates + 2 unknown-puuid regression gates (rabbit flagged the first-cut fix let `$expectedPatientId = null` fall through — `AllergyIntoleranceService::update` + `ConditionService::update` only run the compartment check when non-null, so a crafted `/api/patient/<unknown-valid-uuid>/.../<leaked-uuid>` would still mutate the leaked row because the UPDATE bound pid from the row's own owning pid). All other patient-compartment routes verified safe — services carry WHERE on pid+id. Portal routes out of scope (no `:pid` in path; all session-scoped).
- **background_service OAuth2 coverage (PR #14749, MERGED 2026-10-21 at `c5d09ed9eb`):** Last zero-coverage resource in the §6.2 gap register. Three layers of work:
  - **Scope registry (prod):** Added `background_service` to V2 `$userResources` with `['r','s']` → `user/background_service.rs` + `user/background_service.$run` operation scope + resource description. Added `$run` operation description to `ScopeRepository`.
  - **Route rename (prod):** `POST /api/background_service/:name/run` → `.../$run`. Latent bug — `HttpRestParsedRoute::parseRouteParams` grabs `end($parts)` as the resource, so the required scope was resolving to `user/run.c` which doesn't exist, 401ing every OAuth2 caller since PR #11337 (March 2026). Verified empirically against master code via direct ApiTestClient probe: both pre-fix routes 401'd with the error log showing `user/run.c not in access token` and `user/background_service.$run not in access token`. The strong-dev author (Michael A. Smith) likely tested via session auth (bypasses the OAuth2 scope-check path via `isLocalApi()` / `skipAuthorization`), never minting an OAuth2 token against the surface. Stephen Waite's follow-up #11687 established the `$run` naming convention with an inline comment explaining the parse quirk, but didn't retroactively fix the original `/run` route or register the scopes needed to reach any of the three routes from OAuth2. The whole `/api/background_service/*` surface was effectively dead for OAuth2 clients from March 2026 to this PR.
  - **Test coverage:** New `BackgroundServiceApiTest.php` + `BackgroundServiceApiProbe.php` fixture — 6 tests, 63 assertions. `background_services` table on stock drid carries 5 seeded rows (phimail, MedEx, X12_SFTP, UUID_Service, Email_Service); list returns bare JSON array (no `data:` wrapper) so no empty-list-as-404 bug family. The named `$run` operation runs inline (not via subprocess spawner); probe fixture writes to `/tmp/_api_background_service_probe_sentinel` and the test asserts sentinel line count. Admin ACL (`admin/super`) is satisfied by demo admin user.

**§6.2 batch roadmap complete.** All four batches shipped. **Standard-api route-pattern coverage is effectively 100%** (98/98 after #14749). Further coverage work is behavior-depth-driven (see §8.2 **Scope-system coverage audit** for the V1/V2 scope-testing gap and the deny-path audit) rather than route-count-driven.

#### Post-batch gap register (as of 2026-10-21, post-#14749)

Static route-vs-file audit against `apis/routes/_rest_routes_standard.inc.php` on `upstream/master @ c5d09ed9eb`:

- **98 standard routes total** (96 double-quoted + 2 single-quoted: `GET /api/patient/:puuid/insurance/$swap-insurance` + `POST /api/background_service/$run`).
- **98 covered / 0 uncovered** (100% route-pattern coverage) after shipping #14727 / #14728 / #14729 / #14732 / #14733 / #14734 / #14747 / #14749.

Shipped since the pre-batch snapshot:
- **#14732** (merge `6e75f51c7091`) — `GET /api/patient/{puuid}/insurance/$swap-insurance`
- **#14733** (merge `7c2b21fe7de4`) — allergy POST/PUT/DELETE + medical_problem POST/PUT/DELETE + top-level `/api/allergy` and `/api/medical_problem`
- **#14734** (merge `fc1e82bb190e`) — Appointment pid-scoping fix (patient-compartment single-GET)
- **#14747** (OPEN) — Patient-compartment PUT pid-scoping sweep for allergy + medical_problem + insurance
- **#14749** (merge `c5d09ed9eb`) — `background_service` scope registry + route rename (`/run` → `/$run`) + api coverage

**Zero remaining uncovered standard-api routes.** The route-pattern pass is complete.

**What's left — behavior-depth and deny-path coverage** (tracked in §8.2 and §8.3 going forward):
- V1/V2 scope-resolution end-to-end testing (see §8.2 **V1/V2 scope-set api-tier gap**)
- Insufficient-scope deny paths (positive is comprehensive via `ALL_SCOPES`-grant tokens; negative — tokens with the wrong scope-permission — is thin)
- Behavioral-depth audit of "covered" routes (e.g. `AppointmentApiTest`'s top-level `/api/appointment` coverage is read-only, no POST assertions)
- Pre-existing api-suite flakiness under accumulated `oauth_clients` / `api_token` state (reproducible on master; fresh DB always passes)

### 6.3 /apis/default/fhir/* coverage

Covered in §5.3 — per-resource `Fhir*ApiTest.php` files. Routes without dedicated per-route tests include write paths for less common resources + bulk-data OperationDefinition endpoints.

### 6.4 /apis/default/portal/* coverage

**Full api-tier + external-client coverage as of 2026-10-18.** Shipped sequence:

- `tests/Tests/Api/PortalApiSmokeTest.php` — list + unknown-UUID 404 shapes, scope-set equivalence, cross-patient data isolation (PR #14591, merge `8b94569831`).
- `tests/Tests/Api/PortalApiEncounterPositiveTest.php` — known-UUID encounter positive (PR #14603, merge `6f2c5ff422`).
- `tests/Tests/Api/PortalApiAppointmentPositiveTest.php` — appointment list + 404 shapes; fixed empty-array-as-404 regression in `AppointmentRestController` (PR #14605, merge `5b5b8b3acb`).
- `tests/Tests/Api/PortalApiAppointmentSinglePositiveTest.php` — known-UUID + malformed-UUID 404; fixed uuid-to-binary conversion in `AppointmentRestController::getOneForPatient` (PR #14625, merge `f3fdb45328`).
- `ci/api-explorer/driver-portal.php` — external-client-realism smoke (PR #14673, merge `00b201bee7`): portal patient fixture install → DCR → DB-flip → `user_role=patient` token → `GET /portal/patient` + body-identity check.

Portal-adjacent service-layer tests continue to exist independently:

- `tests/Tests/Services/AdminUnblockCounterResetTest.php` (14)
- `tests/Tests/Services/Portal/*` — various.
- Portal auth path reached directly via `PasswordGrantPatientRoleFlowTest.php` (3, #14531) and the mint-helper library's `AuthCodeFlow::mintPatientRoleToken()` (PR #14591).

### 6.5 Acceptance (release-artifact smoke)

Covered in §3.2 under Acceptance.

### 6.6 External-client api smoke

`sjpadgett/oe-module-api-explorer` via `.github/workflows/api-explorer-test.yml`. Current scope (post-2026-10-18 batch):

- **DCRs four grant configurations** on explorer-side (confidential auth-code, public PKCE, JWT client-credentials, SMART EHR launch).
- **Five driver scripts** exercise distinct OAuth2 round-trips end-to-end:
  - `ci/api-explorer/driver.php` (PR #14533): JWT `client_credentials` → `fhir/Patient` → `Bundle total >= 1`.
  - `ci/api-explorer/driver-browser.php` (PR #14538): confidential auth-code + public PKCE auth-code via Panther/Selenium.
  - `ci/api-explorer/driver-password.php` (PR #14663): DCR + DB-flip + `password` grant (user_role=users) → `fhir/Patient`.
  - `ci/api-explorer/driver-fhir-writes.php` (PR #14665): DCR + DB-flip + `password` grant → FHIR `CareTeam` POST + PUT + GET persistence read-back.
  - `ci/api-explorer/driver-portal.php` (PR #14673): portal patient fixture install + DCR + DB-flip + `password` grant (user_role=patient) → `/apis/default/portal/patient` + body-identity check.
- **Still not covered:** write-path beyond CareTeam, pagination (`next` link not emitted — see §8.3), `_include` (404s — see §8.3), SMART launch (covered by sibling `oe-smart-questionnaire` smoke instead).

Not pinned to a SHA.

---

## 7. External-client testing summary

| Smoke | Repo | Pin? | What it drives end-to-end |
|---|---|---|---|
| `oe-smart-questionnaire` | `openemr/oe-smart-questionnaire` | None (rolling master) | Standalone SMART launch, confidential symmetric client, PKCE S256, `Patient.read` with patient context. |
| `oe-module-api-explorer` | `sjpadgett/oe-module-api-explorer` | None (rolling main) | DCR four grants; JWT `client_credentials` → `fhir/Patient` fetch. |

**Rationale for no SHA pin** (documented in `smart-launch-test.yml` header): occasional transient red (external repo change breaks smoke) is accepted vs. the maintenance tax of pin-bump review cycles.

**Drift detection**: none built in. Breakage surfaces as a red smoke on the next OpenEMR PR; triage then determines whether the regression is in OpenEMR or in the external client.

---

## 8. Gap register

Each entry notes **(category, impact, where to add, rough effort)**. Ordering follows the §Prioritization framework at the top of this document — security first, then testing, then spec compliance, then feature. Within each priority, items are listed with effort size.

### 8.1a Priority 1 — Security gaps: implementation holes (fix the code)

**Phase A audit result: empty.** Three candidates were audited 2026-10-13; all three resolved to either "architecturally safe, add regression gate" or "best addressed upstream." Details:

- **Bulk FHIR `system/*` scope downgrade — AUDITED, SAFE.** Verified empirically (user-scope path: `user/Patient.read` token on `GET /fhir/Patient/$export` returns 401) and traced in code: `HttpRestParsedRoute` extracts `operation='$export'`; `AuthorizationListener::onRestApiSecurityCheck` builds scope `<context>/<resource>.$export`; `HttpRestRequest::requestHasScopeEntity` looks up by scope lookup key (context+resource); `ScopeEntity::containsScope` (lines 313-351) rejects on operation mismatch (line 324-325 — `read` ≠ `$export`). The system-scope path is architecturally identical. **Resolution: moved to §8.1b as regression-gate.**
- **Cross-scope lateral escalation (patient→user→system) — AUDITED, SAFE.** `AuthorizationListener::onRestApiSecurityCheck` derives `scopeType` from `$restRequest->getRequestUserRole()`, not from the token's scope prefix. A patient-role token always builds `patient/*` lookups, a users-role token always builds `user/*`, a system token always builds `system/*`. `ScopeEntity::containsScope` line 318-320 requires exact-context match (`if (getContext() !== otherScope->getContext()) return false`). Lateral escalation is architecturally prevented. **Resolution: moved to §8.1b as regression-gate.**
- **Auth-code validation-order DoS — moved to §9 for upstream coordination.** Phase A re-review under the security-first framework: impact is low-to-trivial (attacker who holds the code gains nothing by burning it; victim cost is one re-authorize click). Local vendor-copy fix (~100 LoC in `CustomAuthCodeGrant::respondToAccessTokenRequest`) carries ongoing maintenance tax against upstream league. The underlying issue is league's `AuthCodeRepositoryInterface` — `isAuthCodeRevoked` is a read-named method; `revokeAuthCode` is void and fires post-issuance. Right home is an upstream interface addition. **Resolution: tracked in §9 — file upstream issue first, PR second.**

### 8.1b Priority 1 — Security gaps: deny-path regression gates (lock current behavior)

Implementation is believed correct; adding tests prevents a silent future regression. Any of these that find a real exploit should be peeled off and handled privately under 8.1a.

**Phase B batch landed in `chore-oauth2-expand-deny-path-gates` (13 new tests + 1 hygiene production fix).** Closed items marked below.

- **Bulk FHIR `system/*` scope downgrade regression gates** — **CLOSED (Phase B).** `OAuth2AttackScenarioTest::testPatientExportDeniesNarrowerScopeToken`, `testGroupExportDeniesNarrowerScopeToken`, `testSystemExportDeniesNarrowerScopeToken`. Three export routes locked: `GET /fhir/Patient/$export`, `GET /fhir/Group/{id}/$export`, `GET /fhir/$export`.
- **Cross-scope lateral escalation regression gates** — **CLOSED (Phase B).** `OAuth2AttackScenarioTest::testUserRoleTokenCannotReachPortalRoute` and `testPatientRoleTokenCannotReachStandardApiRoute`. Both accept 401 or 403 since the server returns 403 (authenticated but insufficient permissions) rather than 401 (unauthenticated-shape).
- **Token-endpoint race on authorization code across worker processes** — **CLOSED (Phase B).** `AuthorizationGrantPublicClientPkceTest::testTwoConcurrentExchangesOfSameCodeCannotBothSucceed` uses `GuzzleHttp\Promise\Utils::unwrap` with `getAsync`/`postAsync` to drive concurrent `/token` POSTs for the same code; asserts at most one succeeds.
- **ID token (OIDC) tampering through the auth-code flow** — **DEFERRED (not shipped in Phase B).** On re-review, OpenEMR does not re-ingest id_tokens except at logout (already covered by `OAuthLogoutIdTokenHintTest`). The auth-code → id_token → client-side-verify path has no server-side re-verification seam to attack. If a seam is added later (eg. a userinfo-signed-response), add the test then.
- **`state` CSRF deny-paths** — **CLOSED (Phase B + PR #14589).** Three variants locked in `OAuthLoginCsrfRejectionTest`: wrong-token-value (`testOAuthLoginPostWithWrongCsrfTokenIsRejected`), missing-key (`testOAuthLoginPostWithMissingCsrfTokenIsRejected` — Phase B), and cross-session replay (`testOAuthLoginPostWithCsrfTokenFromDifferentSessionIsRejected` — PR #14589, asserts the specific "Invalid CSRF" message to distinguish from failed-auth re-render).
- **PKCE deny-paths beyond happy** — **CLOSED (pre-existing + Phase B).** Full matrix covered in `AuthorizationGrantPublicClientPkceTest`: verifier-mismatch (`testPublicClientWrongPkceVerifierIsRejected` — pre-existing), missing-verifier (`testPublicClientMissingPkceVerifierIsRejected` — pre-existing), missing-at-authorize (`testPublicClientRejectedAtAuthorizeWithoutPkce` — Phase B), S256-vs-plain downgrade (`testPublicClientRejectsPkceMethodDowngradeAttempt` — Phase B). The PR #14574 tracker had this marked PARTIAL in error.
- **JWKS malformed / empty keys / null keys at JWT-assertion time (api layer)** — **CLOSED (Phase B).** `JwksBoundaryNegativesTest::testJwtAssertionAgainstClientWithMalformedJwksJsonIsRejected` + `testJwtAssertionAgainstClientWithEmptyKeysArrayIsRejected` + `testJwtAssertionAgainstClientWithNullKeysIsRejected` added. The malformed-JSON and null-keys cases surfaced a production hygiene fix: `JsonWebKeySet::__construct` called `property_exists` on `json_decode`'s null return (throws `TypeError` → HTTP 500) and `foreach` on `$jwks->keys = null` (emits warning → `ErrorException` under the repo error handler). Guard now `!is_object($jwks) || !property_exists($jwks, 'keys') || !is_array($jwks->keys)`; obsolete PHPStan baseline entries for the mixed-property access removed.
- **CORS `Access-Control-Allow-Origin: *` + `Access-Control-Allow-Credentials: true` invariant assertion** — **CLOSED (Phase B).** `OAuth2AttackScenarioTest::testCorsResponseDoesNotCombineWildcardAllowOriginWithCredentials` drives both an unauthenticated request and an authenticated bearer request from a client whose registered redirect_uri base matches the Origin header.
- **Rate-limit / brute-force at HTTP layer** — **CLOSED via PR #14577 (follow-up).** `PasswordGrantRateLimitHttpTest::testTokenEndpointRejectsCorrectPasswordAfterBruteForceThresholdReached` — snapshots `password_max_failed_logins`, `ip_max_failed_logins`, `oauth_password_grant`, admin `users_secure` row, and the request-IP `ip_tracking` row; sentinel-mints a successful token to prove credentials work; then two wrong-password attempts bring the per-account counter to threshold; the third attempt with the CORRECT password is still rejected while the lockout window is active. Six-commit history exercised the full snapshot/restore + capture-flag + setUp-rollback pattern needed for api-layer tests mutating shared `globals` + counter rows.

### 8.2 Priority 2 — Testing (coverage expansion + layer symmetry)

Tests that improve regression signal without changing production behavior.

**In-flight external contributor PRs** chip at this register — status collected 2026-10-13, re-confirmed 2026-10-18 (originally all nine open + #14653). **Status as of 2026-10-18 23:55 UTC:** #14653 **MERGED** (SHA `876f40e548`) — ride-along nitpick (data-provider `@codeCoverageIgnore` on `addressSearchProvider`) addressed via commit `413e0e357d` on PR #14675. The other 9 contributor PRs remain open. These are open on `openemr/openemr`; coordination hint on each. Column key: ✅ CI green • ⚠️ transient / infra failure not caused by diff • 🚫 CI gated (contributor's workflow runs not yet triggered — usually first-time-contributor approval or codecov) • 👁️ reviewers.

| PR | Author | Shape | CI | Reviewers | Lane in this register |
|----|--------|-------|----|-----------|-----------------------|
| [#14194](https://github.com/openemr/openemr/pull/14194) FHIR OperationDefinition api tests | namespaceMarcello | test-only (196/0, 2 files) | ✅ 75 green, 1 pending | — | Resource-level FHIR api coverage; also exercises **authorization skip list** (adjacent to our §8.1b dispatcher work) |
| [#14195](https://github.com/openemr/openemr/pull/14195) FHIR Procedure api tests | namespaceMarcello | test-only (561/0, 1 file) | ✅ 71 green, 1 pending | 👁️ coderabbit | Resource-level FHIR api coverage |
| [#14219](https://github.com/openemr/openemr/pull/14219) FHIR Group api tests | namespaceMarcello | test-only (418/0, 2 files) | ⚠️ Upgrade-from-5.0.0/setup failed (infra, not diff) | 👁️ coderabbit | Resource-level FHIR api coverage |
| [#14190](https://github.com/openemr/openemr/pull/14190) ValueSet `_id` → `list_options.option_id` | namespaceMarcello | prod fix + api tests (406/4, 3 files) | ✅ 75 green, 1 pending | 👁️ coderabbit | FHIR resource prod fix + api coverage |
| [#14218](https://github.com/openemr/openemr/pull/14218) Specimen honors `status` search param | namespaceMarcello | prod fix + api tests (562/5, 3 files) | ✅ 75 green, 1 pending | 👁️ coderabbit | FHIR resource prod fix + api coverage |
| [#14220](https://github.com/openemr/openemr/pull/14220) Media reports deleted docs as entered-in-error | namespaceMarcello | prod fix + api tests (422/1, 3 files) | ✅ 73 green, 1 pending | — | FHIR resource prod fix + api coverage |
| [#14292](https://github.com/openemr/openemr/pull/14292) Dead refresh token → 400 invalid_grant | namespaceMarcello | prod fix + isolated test (197/1, 3 files) | ✅ 73 green, codecov patch/project failing (test-code excluded, not blocking) | — | Grant-handler lane; **check overlap** with our #14658 (refresh scope persistence) before merging |
| [#14511](https://github.com/openemr/openemr/pull/14511) Ignore duplicate consent submissions | shwetanktomar | prod fix + isolated test (56/0, 2 files) | 🚫 full matrix not yet triggered (2/2 checks only) | — | AuthorizationController; **check overlap** with our #14628 (consent-scope narrowing) + #14633 (consent-page render) before merging |
| [#14516](https://github.com/openemr/openemr/pull/14516) Complete server scope catalog types | sn1thy | prod + isolated test (142/149, 9 files) | 🚫 full matrix not yet triggered (2/2 checks only) | — | ScopeRepository + AuthorizationController; **check overlap** with our #14662 (unconstrained-scope depth) before merging |
| [#14653](https://github.com/openemr/openemr/pull/14653) REST + FHIR endpoint bugs from 8.4 client-side testing | juggernautsei (commits by David Lavoy) | prod fix + tests (25 files across services + api + service-layer tests) | ✅ | — | **MERGED 2026-10-18** (SHA `876f40e548`). 9 commits, each with a regression-gate test: ListService uuid assign + stringify (api tier locked via #14680 MedicationApiTest + #14681 Surgery/DentalIssue), InsuranceCompany uuid assign + stringify (api tier locked via #14680 + further by #14688), VitalsService preserve uuid on update (api tier locked via #14689), PatientTransaction return transaction id (api tier locked via #14689), C_Document skip CSRF for API session, FHIR Practitioner `users.`-prefix, FHIR Goal `fcp.code`, FHIR Organization address columns per-table, FHIR ValueSet `_id` → `option_id`. Nitpick (data-provider `@codeCoverageIgnore`) addressed in PR #14675. |

**Coordination hints:**
- Several PRs modify `tests/Tests/Api/ApiTestClient.php` — merge-order favors our PR #14574 (does not touch it). Consistent helper naming (`assertOAuthUnauthorizedResponse`) is worth preserving across the series.
- Three OAuth-adjacent PRs (#14292, #14511, #14516) overlap in scope with shipped work from the scope-system / consent / refresh-token audit batches — review each for duplication or regression against our tests before merging.
- #14653's prod-fix scope is wider than the audit docs track — see §6 Route-catalog, not §5/§8.
- Three pure-test FHIR coverage adds (#14194, #14195, #14219) are lowest-friction to triage; the three FHIR resource bugfixes (#14190, #14218, #14220) need code review but are self-contained.

**Suggested landing order (lowest-friction first):**
1. #14194 / #14195 / #14219 — pure FHIR test adds, no prod change.
2. #14190 / #14218 / #14220 — FHIR resource bugfixes with tests; standalone.
3. #14292 / #14511 / #14516 — OAuth-adjacent; overlap-audit against our shipped work first.
4. ✅ ~~#14653 — multi-service bugfix sweep; biggest surface, needs its own review pass.~~ **Done 2026-10-18.**

- **Inferno signal-swallow bug + residual cert items** — ✅ **CLOSED (status audit 2026-10-17).** Historical context: `ci/inferno/run.sh` previously wrapped phpunit in an if-conditional that disabled `set -e`, so cert-side failures showed green. Known residuals that had been silently failing: Bulk Group Compartment Export + Smoking Status Observations. **Current state:**
  - **The signal-swallow bug is fixed** (`ci/inferno/run.sh` lines 222-232 now use `if ! run_testsuite; then local exit_code=$?; echo "FAILURE: ..."; exit "${exit_code}"; fi` — exit code propagates correctly through the `if !` wrapper).
  - **Both residuals pass cert.** `gh run view` on recent runs shows `7.2.Group Compartment Export Tests: pass` and `4.14.Smoking Status Observation Tests: pass`.
  - **Recent CI failures are infra, not cert.** The 2 failures on 2026-10-16 (runs `135bfaf50a`, `4c9369906b`) were caused by rsync bind-mount race conditions (`/couchdb/original/data` and `/openemr` not found) during the CI setup phase, NOT by cert test regressions. The underlying Inferno tests themselves passed. Importantly, the signal-swallow fix is what lets these infra flakes surface as RED instead of silent-green — this is the correct behavior.
  - **Analysis of CI pattern:** last 20 master runs show 16 success, 2 failure (both infra), 2 in-progress. Normal cert signal is now visible and the historical silent-green problem is resolved.

- **Inferno cert scope — concretely documented (2026-10-17 audit).** On every master run the certification suite exercises **exactly two top-level Inferno test groups via phpunit** (`vendor/bin/phpunit --testsuite certification`, 10 phpunit tests / 344 assertions / ~6 min):
  - **§4 Single Patient API (US Core 3.1.1)** — Driven by `tests/Tests/Certification/HIT1/G10_Certification/SinglePatient311APITest.php` + `SinglePatient700APITest.php`. Runs 3× (once per fixture patient: Alice Jones, Jeremy Bates, Happy Child). The G10 suite handles OAuth2 setup end-to-end: DCR + admin-approve + /authorize + login + consent + /token (auth-code grant) + refresh_token exchange (confirmed from the openemr log: `CustomRefreshTokenGrant::getClientCredentials` + `respondToAccessTokenRequest` fire during the run). Then exercises per-resource FHIR reads + search + Provenance revInclude.
    - **Sections covered:** 4.1 Capability Statement, 4.2 Patient, 4.3 AllergyIntolerance, 4.4 CarePlan, 4.5 CareTeam, 4.6 Condition, 4.7 Implantable Device, 4.8 DiagnosticReport for Report-and-Note, 4.9 DiagnosticReport for Lab, 4.10 DocumentReference, 4.11 Goal, 4.12 Immunization, 4.13 MedicationRequest, 4.14 Smoking Status Observation, 4.15 Pediatric Weight-for-Height, 4.16 Laboratory Result Observation, 4.17 Pediatric BMI-for-Age, 4.18 Pulse Oximetry, 4.19 Pediatric Head Circumference Percentile, 4.20-4.25 Body Height/Temp/BP/Weight/Heart/Resp Rate, 4.26 Procedure, 4.27 Encounter, 4.28 Organization, 4.29 Practitioner, 4.30 Provenance, 4.31 Clinical Notes Guidance, 4.32 Missing Data.
  - **§7 Multi-Patient Authorization and API (Bulk Data)** — Driven by `tests/Tests/Certification/HIT1/G10_Certification/BulkPatientExport311APITest.php`. Full Bulk Data flow:
    - 7.1 Bulk Data Authorization (invalid grant_type / invalid client_assertion_type / invalid JWT / correct info / response-body shape — SMART Backend Services with JWT client assertion)
    - 7.2 Group Compartment Export (CapabilityStatement support, auth required, 202 + Content-location, status check, output format)
    - 7.3 Group Compartment Export Validation (NDJSON access token required, per-resource US Core profile conformance, Group patient-ID matching)
    - 7.4 Group Compartment Export Cancel (DELETE returns 202)
  - **What Inferno does NOT run (contrary to the §8.2 "external realism" entries' prior implicit assumption):**
    - ❌ SMART App Launch standalone cert group — the `smart_app_launch_version` input is submitted but Inferno logs `Unknown input smart_app_launch_version for g10_certification-g10_single_patient_api` and treats it as US Core 3.1.1 anyway.
    - ❌ EHR Launch cert group — not exercised.
    - ❌ Public-client (no secret) PKCE cert group — not exercised.
    - ❌ US Core 7.0.0 — `SinglePatient700APITest.php` exists and submits `us_core_version=us_core_7` but Inferno logs `Unknown input us_core_version` and runs the test as US Core 3.1.1. There is no separate v7 cert path in the current master config.
    - ❌ Terminology Server cert group — not exercised (and tx.fhir.org dependency is intentionally avoided; see PR #14498).
    - ❌ CDS Hooks — not exercised.
    - ❌ FHIR writes — the cert surface is read-only.
    - ❌ Password grant — not exercised (not SMART-approved).
    - ❌ Error-path consent flows (denied-consent, expired launch token, missing patient context) — not exercised.
  - **Grants and transports Inferno DOES exercise end-to-end:**
    - Authorization-code grant (confidential client, client_secret_post transport) — ✅ via §4 setup
    - Refresh-token grant (confidential client) — ✅ confirmed from live run log (`CustomRefreshTokenGrant` invoked)
    - Client-credentials grant with `urn:ietf:params:oauth:client-assertion-type:jwt-bearer` — ✅ via §7.1 Bulk Data Authorization
    - `_revInclude:Provenance:target` on every US Core resource — ✅
  - **Grants and transports Inferno does NOT exercise:**
    - Public client PKCE — the suite uses a confidential client throughout.
    - Password grant — not SMART-approved.
    - Session-cookie / cross-origin requests from a browser — Inferno is API-only.
  - **Non-Inferno cert file:** `tests/Tests/Certification/HIT1/G9_Certification/CCDADocRefGenerationTest.php` runs in the same phpunit suite but is NOT Inferno-driven — it tests CCDA document generation against a private expected-output fixture in `inferno-files/`.
- **Portal API surface** — 5 routes. Progress 2026-10-15 → 2026-10-16:
  - **PR #14591** (merge `8b94569831`) shipped positive-shape smoke coverage for patient + encounter list (via password-grant), plus the full layered plan below (A/B1/B2/C/D1/D2) and the new `tests/Tests/Support/OAuth2/` helper library.
  - **PR #14603** (merge `6f2c5ff422`) added positive-shape coverage for `GET /portal/patient/encounter/{known-uuid}` — first external consumer of the helper library.
  - **PR #14605** (merge `5b5b8b3acb`) fixed `AppointmentRestController` — the three list methods were returning 404 for empty results because `RestControllerHelper::responseHandler` treats falsy as 404 and `[]` is falsy. Added `listResponse()` helper; `GET /portal/patient/appointment` now returns 200 + `"[]"` for empty. Also surfaced that my PR #14591 inline comment attributing the appointment-route 404 to a "scope-filter mystery" was WRONG — `patient/appointment.read` survives DCR → ScopeRepository → finalizeScopes → JWT fine; the real cause was this empty-array-as-404 bug all along. Sibling to issue #14581 (same family, different method).
  - **PR #14625** (merge `f3fdb45328`) fixed `AppointmentRestController::getOneForPatient` — the deferred "known-UUID positive" case uncovered a **latent production bug**: raw-string UUIDs were being passed to `search()` which wraps as `StringSearchField` with exact-match semantics against `BINARY(16)` columns. The route could never return 200 for any real appointment lookup. Fix mirrors `EncounterService::getEncounter`'s pattern (`TokenSearchField` + `TokenSearchValue(..., ..., true)` for UUID → binary conversion). Also added a format guard (`UuidRegistry::isValidStringUUID`) so a malformed path param returns the pre-fix 404 shape instead of a 500 from the uuid-to-binary conversion.
  - **Portal API api-tier coverage is now fully closed. ✅** Only E (external-client realism) remains open and lives on a different axis.
  - **Layered coverage plan** (for Portal API, generalizable to future endpoint families):
    - **A) Scope-match semantics** (isolated) — ✅ covered by `ScopeConstraintEnforcementWiringIsolatedTest` + ScopeEntity tests.
    - **B1) Mint path — password grant / patient** — ✅ `PasswordGrantPatientRoleFlowTest`.
    - **B2) Mint path — auth-code / patient (via PKCE)** — ✅ PR #14591 via `AuthCodeFlow::mintPatientRoleToken()`.
    - **C) Endpoint reach + response shape** — ✅ ALL 5 ROUTES covered:
      - `/portal/patient` (patient — PR #14591)
      - `/portal/patient/encounter` list (PR #14591) + unknown-UUID 404 (PR #14591) + known-UUID 200 (PR #14603)
      - `/portal/patient/appointment` list (PR #14605) + unknown-UUID 404 (PR #14605) + malformed-UUID 404 (PR #14625) + known-UUID 200 (PR #14625)
    - **D1) Scope-set equivalence across mint paths** — ✅ PR #14591. Confirmed password-grant and auth-code produce equivalent resource-scope sets (nonce excluded as known-benign auth-code OIDC artifact).
    - **D2) Cross-patient data isolation** — ✅ PR #14591 (`testGetPortalPatientIsolatesAcrossDifferentPatientBearers`).
    - **E) Full-stack realistic (external client)** — ✅ **CLOSED via PR #14673** (merge `00b201bee7`). New `ci/api-explorer/driver-portal.php` (Guzzle + OE bootstrap for the portal patient fixture) exercises `GET /apis/default/portal/patient` end-to-end from a real third-party client. 5 steps: portal patient fixture install → DCR with `openid api:port patient/patient.read patient/encounter.read offline_access` → DB-flip to `grant_types=password` + `is_enabled=1` → `POST /oauth2/default/token` with `user_role=patient` (fail-fast on scope-filter drift at the portal-grant leg) → `GET /apis/default/portal/patient` asserting 200 + non-empty JSON array identifying the fixture by email. Diverges from the three sibling drivers by bootstrapping OE because `PortalPatientFixtureManager` uses `AuthHash::passwordHash` + `QueryUtils`; `run.sh` invokes via `su apache` because `RootCliGuard` refuses uid=0. Rabbit followup fix: original `exit(N)` calls skipped the enclosing `finally` block and leaked fixture + oauth_clients rows on failure — now throws RuntimeException with the exit code so cleanup always runs.
  - **Scaffolding — `tests/Tests/Support/OAuth2/` reusable mint-helper library** (landed via PR #14591):
    - `DcrClient::registerConfidential / registerPublic / enableViaDb / remove`
    - `PasswordGrant::mintUserRoleToken / mintPatientRoleToken`
    - `AuthCodeFlow::mintUserRoleToken / mintPatientRoleToken` (full /authorize → login → consent → /token via Guzzle + DomCrawler)
    - All three use orphan-row try/catch guards. Future api-tier tests: ~20 lines each after this library.
    - Current consumers: PR #14591 (`PortalApiSmokeTest`), PR #14603 (`PortalApiEncounterPositiveTest`), PR #14605 (`PortalApiAppointmentPositiveTest`), PR #14625 (`PortalApiAppointmentSinglePositiveTest`). Validates the "future tests become ~20 lines" scaffold goal.
    - Not yet landed: `JwtAssertion::mintSystemToken` + public-client PKCE variant on `AuthCodeFlow`. Add when first consumer needs them.
    - Separate from `tests/Acceptance/Support/OAuth2/AuthCodeFlow.php` (artifact-targeted; admin-panel enable).
    - **Does NOT obsolete `api-explorer`** — complementary coverage axes. Helper library owns fast-breadth internal matrix; api-explorer owns real-external-client realism axis.
  - **Lessons:**
    - **Scope-filter mysteries → probe the issuance pipeline first.** When a route 404s and the obvious hypothesis is "scope dropped by issuance," hands-on probes of DCR → ScopeRepository → finalizeScopes → JWT payload are the right first step BEFORE blaming the scope path. PR #14605 surfaced that the appointment "scope mystery" was an empty-array-as-404 controller bug all along.
    - **"Needs plumbing investigation" deferrals may be hiding real bugs.** PR #14605 deferred the known-UUID case to "FhirSearchWhereClauseBuilder plumbing"; PR #14625 investigated and found the controller couldn't ever return 200 for a real appointment. If a test case is deferred as "needs more setup plumbing," spend 15 minutes probing the service layer directly before accepting the deferral — the fixture may not need plumbing at all; the production code might be broken.
**External-realism register (post-Inferno-audit categorization 2026-10-17).** The "E" axis has two sub-tiers now that Inferno coverage is concretely documented above:
  - **E1 — ONC-cert external realism (Inferno)** — covered on master for auth-code + refresh-token + client_credentials-with-JWT against US Core 3.1.1 reads + Bulk Data Group Export. Full coverage detail is in the Inferno scope section above.
  - **E2 — non-cert external realism (api-explorer / smart-questionnaire)** — the entries below. These are behaviors Inferno does NOT exercise.

- **EHR launch end-to-end via a real client** — ✅ **Already covered on master** (2026-10-18 audit correction). The gap entry was stale — written against an earlier state before `testEhrLaunchSkipsPatientSelectAndLandsOnApp` landed in `tests/Tests/SmartLaunch/SmartLaunchQuestionnaireTest.php`. The current test:
  - signs in as admin → sets patient context via `demographics.php?set_pid=1` → loads `fhir_assessments.php` → grabs `client_id` / `csrf` / `intent` from the real smart-launch button;
  - fires GET to `interface/smart/ehr-launch-client.php` → OpenEMR mints a launch token → redirects to `oe-smart-questionnaire`'s `public/launch.php?iss=&launch=`;
  - the app bounces to `/authorize?launch=<token>` → login → consent;
  - **core EHR-launch invariant asserted:** `/smart/patient-select` is SKIPPED (patient context flows via the launch token, not the app's standalone picker);
  - lands on the app's `index.php` → reads the seeded Questionnaire via the bearer.

  `oe-smart-questionnaire`'s `public/launch.php` already explicitly supports EHR launch mode (`?iss=<fhir base>&launch=<opaque>`) — no external PR was ever needed. Isolated-tier `EhrLaunchPatientContextTest` continues to cover the `processAuthorizeFlowForLaunch` private method directly.
- **Public client (no secret) PKCE from an external client** — ✅ **CLOSED via PR #14538** (merge `ce634555730`). `driver-browser.php` drives Panther/Selenium through a public-client PKCE round-trip (DCR with `token_endpoint_auth_method=none` + `patient/*` scopes → `/authorize` + `code_challenge` → login → consent → `/token` with `code_verifier`). Catalogued here for completeness after the 2026-10-18 doc audit noticed the entry was stale.
- **Backend-services (`system/*`) SMART launch** — ✅ Inferno §7.1 Bulk Data Authorization exercises client_credentials with JWT assertion end-to-end (invalid grant_type / invalid client_assertion_type / invalid JWT / correct info / response-body shape). §7.2–7.4 continue the flow with `$export` + status polling + NDJSON retrieval + cancel. The external-client-realism version (api-explorer driver) would add a second external viewpoint, but is **optional** given Inferno is actually driving this at cert quality. **(Where: optional `api-explorer` lane. Effort: medium.)**
- **Password grant via a real third-party client** — ✅ **CLOSED via PR #14663** (merge `8fb6a90eb7`). New `ci/api-explorer/driver-password.php` (Guzzle, server-to-server, no browser) drives DCR → DB flip (`grant_types='password'`, `is_enabled=1`) → `/oauth2/default/token` with `grant_type=password` + `user_role=users` + admin/pass → FHIR `Bundle` assertion. The explorer's own Scope Lab lane could expose password grant via `scope_lab.php?action=run&grant=password`, but driving it from a CLI needs CSRF-seeding plumbing more complex than the token-endpoint contract this smoke needs to cover; rationale documented in the driver docblock. `seed-globals.sql` additionally sets `oauth_password_grant=3` as defence in depth after drid runs (the dev-easy compose already exports it).
- **Auth-code grant via a real third-party client** — ✅ Inferno §4 setup exercises auth-code grant end-to-end (DCR + admin-approve + /authorize + login + consent + /token). `api-explorer` has an auth-code lane but is now **duplicative with Inferno** at the "external client sees it work" axis.
- **Refresh-token grant via a real third-party client** — ✅ Inferno exercises refresh exchange during §4 (confirmed from the openemr log — `CustomRefreshTokenGrant::getClientCredentials` + `respondToAccessTokenRequest` fire). `api-explorer` could add a second external viewpoint, but is **optional**.
- **FHIR writes from a real third-party client** — ✅ **CLOSED via PR #14665** (merge `945a64c595`). New `ci/api-explorer/driver-fhir-writes.php` (Guzzle, server-to-server) reuses `driver-password.php`'s DCR + DB-flip + password-grant pattern to mint a `user/CareTeam.write` bearer, then drives a POST → PUT → GET round-trip against `/apis/default/fhir/CareTeam` (fixture is a minimal CareTeam with `subject = Patient/<uuid>` + `participant = Practitioner/<admin uuid>`). Scope assertion fires before any write (fail-fast on scope-filter drift). CareTeam over Patient so the test exercises both a subject-reference and a participant-reference on the write path; no DELETE lane because FHIR CareTeam has no DELETE route. Same hardening as the sibling drivers (HTTPS scheme gate, `allow_redirects=false` on credential-bearing requests).
- **Pagination / `_sort` / `_include` / `_revinclude` smoke** — 🟡 Partially covered. ✅ Inferno §4 exercises `_revInclude:Provenance:target` for every US Core resource. ✅ **`_sort` on Patient** closed via PR #14667 (merge `8e32930542`) — new `tests/Tests/Api/FhirSearchModifiersApiTest.php` adds 3 tests (asc by date, desc by date, asc by string) locking the Patient sort path end-to-end. **The PR audit surfaced three contract gaps** worth tracking separately (now in §8.3 "FHIR search-modifier contract gaps"): `_count` next-link not emitted, `_include` 404s with empty body, `_sort` on non-Patient/Task is a silent no-op. These are not regression-gate items — they're implementation gaps advertised in metadata that the audit should file as new work.
- **Scope-narrowing on consent.** — ✅ **CLOSED via PR #14628** (merge `fa5b6eda59`). New `ConsentScopeNarrowingTest` with 3 tests covers staff + patient + full-grant positive-control. Also extends `AuthCodeFlow` with an optional `consentGrantsOverride` parameter — any future consent-related regression test is now ~20-30 lines. Finding: the consent-page scope intersection works correctly in production (all tests pass); the test is now a regression gate rather than a bug catcher.
- **Error-path browser flows in SMART launch** — ✅ **Partially closed via PR #14666** (merge `c27be58060`) and **extended via PR #14668** (merge `44cbd6af98`). `SmartLaunchQuestionnaireTest` now covers four distinct error-path gates:
  - Tampered-launch-token rejection at `/oauth2/default/authorize` (asserts `error=invalid_request` + the specific hint naming the launch parameter so a "guard moved" regression is caught — PR #14666).
  - Mismatched-redirect-uri rejection at `/oauth2/default/authorize` (asserts `error=invalid_client` AND the browser's URL host stays on openemr, not the attacker-chosen host — SMART open-redirect attack class — PR #14666).
  - **Bad-csrf rejection at `ehr-launch-client.php`** — the EHR-launch entry point that mints the launch token. Asserts the response contains `xlt('Authentication Error')` AND the browser stays on `/interface/smart/ehr-launch-client.php` (no silent 302 to the app's launch_uri — the launch token should never have been minted). Catches a trust-boundary regression where an attacker-controlled URL could trigger a legitimate launch at a client of their choosing (PR #14668).
  - **Unknown-client-id rejection at `ehr-launch-client.php`** — asserts the generic `"Unknown system error occurred"` string, no redirect, AND that the submitted client_id is NOT echoed back (information-disclosure negative assertion — PR #14668).

  Private helper `readRegisteredSmartClient()` pulls live `client_id` + registered `redirect_uri` from the smart-questionnaire's `clients.json` (PR #14666); the EHR-launch tests inline the admin-login + patient-context setup same style as the existing happy-path test.

  **Still open:** denied-consent end-to-end — blocked on the §8.3 user-denial code change (`scope-authorize.html.twig` Cancel button is `history.back()` only; no deny-POST path exists today). "Missing patient context" was reclassified as not-an-error-path — OpenEMR does not require `launch=` at `/authorize`; absence falls through to standalone patient-select.
- **`HttpRestRequest::requestHasUnconstrainedScopeEntity` isolated depth** — ✅ **CLOSED via PR #14662** (merge `ca3197acd1`). Audit entry originally mis-named this as "ScopeConstraintFilterer"; there is no such class — the thing that could use more isolated coverage was `HttpRestRequest::requestHasUnconstrainedScopeEntity`, whose single pre-PR test file was 6 cases while the twin `ResourceConstraintFilterer` had 10. PR extended the provider to 16 cases + 2 dedicated test methods (19 tests, 36 assertions). Locks fail-closed defaults on both axes (empty scope map, permission-insufficient hits), context-mismatch isolation, v1 read-vs-write permission containment (rabbit follow-up), and order independence.
- **`IntrospectionTest` isolated split** — ✅ **CLOSED via PR #14664** (merge `21f8fcb7ed`). New `TokenIntrospectionRestControllerIsolatedTest` adds 7 isolated tests, 13 assertions, covering `validateInitialRequestParameters` parameter-shape branches (empty token, missing client_id ± refresh_token hint) + the inactive-response path through `postAction` (shape, session invalidation + id rotation per RFC 7662, non-distinguishability of fields on failure). DB-backed branches stay in the api tier. Notes worth preserving: `ServerConfig::__construct` reads session + globals + `$_SERVER['HTTP_HOST']` so a `ServerConfig` mock has to be injected before even the pre-DB validation call chain runs clean in isolation; `HttpRestRequest::create(...)` is required instead of `new HttpRestRequest(...)` because `convertRestRequestToPsrRequest` builds a PSR-7 URI via nyholm/psr7, which rejects `"http://:/"`.
- **SMART authorization controller api coverage** — ✅ **CLOSED via PR #14633** (merge `deaac61cce`). `ConsentPageRenderTest` adds 5 shape assertions on the rendered consent-page HTML: csrf input, proceed button, cancel control, registered-client-name display, action under `/oauth2/` namespace, plus staff/patient parity. `AuthCodeFlow` gained reusable `renderConsentPageForUserRole` + `renderConsentPageForPatientRole` helpers — future consent-screen regression tests are ~10 lines each. Finding: consent form POSTs to `/oauth2/default/device/code` (league OAuth2 library's shared confirm handler used by both auth-code and device-code), NOT `/scope-authorize-confirm` as I'd originally assumed; the template's `redirect` variable is populated server-side with whatever league routes through.
- **FHIR Bulk broader positive coverage** — ✅ **substantially covered by Inferno** (post-signal-swallow-fix). Inferno §7 drives the full Bulk Data flow end-to-end: §7.1 Bulk Data Authorization (6 sub-tests: invalid grant_type / invalid client_assertion_type / invalid JWT / correct info / response-body JSON shape), §7.2 Group Compartment Export (CapabilityStatement support, auth required, 202 + Content-location, status check, output type/url), §7.3 Group Compartment Export Validation (NDJSON access token required, per-resource US Core profile conformance for Patient/AllergyIntolerance/CarePlan/CareTeam/Condition/Device/DiagnosticReport/DocumentReference/Goal/Immunization/MedicationRequest/Observation/Procedure/Encounter/Organization/Practitioner/Provenance/Location, Group patient-ID matching), §7.4 Group Compartment Export Cancel (DELETE 202). Internal api-tier addition would be marginal signal — the cert surface is already fully exercised. Any specific regression surface not reachable through §7 would still be worth an api-tier test, but there's no identified gap.

#### Scope-system coverage audit (2026-10-17)

Scope-related code is one of the most security-sensitive surfaces in the OAuth2 stack. A regression in scope parsing, consent intersection, registration filtering, or context matching could silently widen access, bypass user intent, or confuse role boundaries. Audit of today's test coverage:

**Covered — ~130-150 assertions across the stack:**

| Component | Test file | Count | Covers |
|---|---|---|---|
| `ScopeEntity` parse + `containsScope` | `Isolated/.../ScopeEntityTest.php` + `Unit/.../ScopeEntityTest.php` | 17 + 9 | Scope-string parsing, operation/resource extraction, context, containsScope (incl. v1→v2 permission expansion) |
| `ScopePermissionObject` CRUDS | `Isolated/.../ScopePermissionObjectTest.php` | 25 | `read`→`rs` expansion, `write`→`cud`, constraint query parsing, CRUDS ordering, v1 shortcut handling |
| `ScopeConsentResolver` intersection | `Isolated/RestControllers/SMART/ScopeConsentResolverIsolatedTest.php` | 16 | Posted checkbox state → granted scopes, uncheck narrows, unregistered scope filtering, fail-closed without registration, posted-values-outside-request ignored |
| `ScopeRepository` finalization | `Unit/.../Repositories/ScopeRepositoryTest.php` | 13 | Discovery of registered scopes, finalization behavior |
| `ScopeEntityContext` | `Unit/.../Entities/ScopeEntityContextTest.php` | 8 | Context resolution semantics (patient/user/system) |
| `ScopeDelimiterRejection` | `Isolated/.../ScopeDelimiterRejectionIsolatedTest.php` | 7 | Colon-form rejection for non-legacy contexts (prevents `system:Patient.read` sneaking past slash-based matching) |
| `ScopePermissionParser` SMART v2 | `Isolated/RestControllers/SMART/ScopePermissionParserIsolatedTest.php` | 5 | SMART v2 scope parsing |
| `ResourceConstraintFilterer` | `Isolated/FHIR/SMART/ResourceConstraintFiltererTest.php` | 10 | Per-resource field constraint (e.g. category filter for DocumentReference) |
| `ScopeConstraintEnforcementWiring` | `Isolated/FHIR/SMART/ScopeConstraintEnforcementWiringIsolatedTest.php` | 2 | `FhirDocumentReferenceRestController` applies constraint filterer; bulk uses unconstrained check |
| `ResourceScopeEntityListGrantsScope` | `Isolated/.../ResourceScopeEntityListGrantsScopeIsolatedTest.php` | 3 | List-of-scopes grants a specific scope |
| `ConsentScopeNarrowing` (api-tier) | `tests/Tests/Api/ConsentScopeNarrowingTest.php` (PR #14628) | 3 | End-to-end confirmation that consent-page unchecking narrows the issued token |
| `OAuth2AttackScenarioTest` scope-adjacent | `tests/Tests/Api/OAuth2AttackScenarioTest.php` | ~5 of ~30 | Password grant wrong-role rejection; refresh with made-up token; FHIR rejects wrong-signed/empty bearer |
| **Scope-set equivalence** (D1 from PR #14591) | `PortalApiSmokeTest::testPasswordGrantAndAuthCodeMintEquivalentScopeSets` | 1 | Password-grant and auth-code mint produce identical scope sets for the same request |
| Inferno US Core 3.1.1 scope conformance | §4 cert groups | 30+ sub-tests × 3 patients | Resource-level scopes grant correctly for every US Core profile |
| Inferno Backend Services JWT→scope | §7.1 Bulk Data Auth | 6 sub-tests | client_credentials + JWT assertion maps to correct `system/*` scopes |

**Security-regression classes and their current gates:**

| Bug class | Risk shape | Current gate |
|---|---|---|
| Scope up-grading (request `patient/Patient.read`, server grants `patient/*.*`) | ✅ ScopeEntityTest + ScopeConsentResolver + ConsentScopeNarrowingTest |
| Scope-category confusion (patient/* matches user/* via context parse bug) | ✅ ScopeEntityContextTest + PR #14574 role-mismatch deny gates |
| Colon-form bypass (`system:Patient.read` treated like `system/Patient.read`) | ✅ ScopeDelimiterRejectionIsolatedTest |
| Permission upgrade (`read` accepted as `write`) | ✅ ScopePermissionObjectTest |
| CRUDS ordering (`csu` sneaks past because regex is wrong) | ✅ ScopePermissionObjectTest::isOrderedCrudString |
| Consent intersection bypass (user unchecks scope, token keeps it) | ✅ 16 isolated + 3 api-tier |
| Registration-filter bypass (unregistered scope gets through DCR) | ✅ ScopeConsentResolver `testRegistrationFilter*` (5 of the 16) |
| Fail-open on empty registration | ✅ `testRegistrationFilterFailsClosedWithoutRegisteredScopes` |
| ONC-cert US Core 3.1.1 scope conformance | ✅ Inferno §4 (3-patient × 30+ cert groups per run) |
| Backend-services JWT→scope flow | ✅ Inferno §7.1 + attack-scenario alg-none + foreign-key tests |
| Scope-set equivalence between grants | ✅ PR #14591 D1 |
| **Narrow-then-refresh scope persistence** — if user grants only `patient/Patient.read` but requested both, the refresh must NOT re-widen to the original request | ✅ PR #14658 (merge SHA `52ac4f8551`) — `RefreshTokenScopePersistenceTest::testRefreshWithExplicitScopePreservesNarrowedSet` + `testRefreshWithUpgradedScopeIsRejectedOrNarrowed` |
| **Scope downgrade on refresh** — RFC 6749 §6 allows the client to narrow scope on refresh via a `scope=` parameter. Does OpenEMR accept it and narrow correctly? | ✅ PR #14659 (merge SHA `36b15901d3`) `PublicClientPkceMintTest::testPublicClientScopeDowngradeOnRefreshIsHonored` + PR #14658 `RefreshTokenScopePersistenceTest` narrowing cases |
| **Deny-consent error shape** — user clicks Cancel/does not submit `proceed=1`. Must return no code and redirect with `error=access_denied` per RFC 6749 §4.1.2.1 | ❌ **Spec-compliance gap, not security — see §8.3 user-denial entry.** Fixing requires a code change (short-circuit `authorizeUser` when `proceed` absent + wire the Cancel button to POST `deny=1`); will become testable once that lands. |
| Public-client (no secret) PKCE at mint-level | ✅ PR #14659 — `PublicClientPkceMintTest` — 3 api tests covering mint, consent-narrowing, and scope-downgrade-on-refresh |

**Addressing plan (2026-10-17, shipped 2026-10-18):**

- ✅ **Narrow-then-refresh scope persistence** — **PR #14658 MERGED 2026-10-18** (`52ac4f8551`). `RefreshTokenScopePersistenceTest` — 2 api tests, 52 assertions. Confirms (a) narrowed scope set carries through refresh when the client passes `scope=` naming only the granted subset, and (b) a widening attempt is either rejected (RFC-strict: `invalid_scope` + the refresh token still works for the granted set, proving the rejection was scope-driven) or quietly narrowed back to the bound set with a non-empty access_token (defensive gate against tokenless-200 regressions). Shipped shape resulted from 2 rabbit rounds applied + 1 rabbit decline rebutted empirically (claim that `scope` field is omitted on `offline_access` responses was wrong — PHP late binding resolves to OpenEMR's `IdTokenSMARTResponse::getExtraParams()` override which explicitly adds `scope`).
- ✅ **Public-client PKCE full-flow mint helper + scope-downgrade-on-refresh** — **PR #14659 MERGED 2026-10-18** (`36b15901d3`). `PublicClientPkceMintTest` — 3 api tests, 71 assertions. The `AuthCodeFlow` helper gained `mintUserRolePublicClient` + `exchangeRefreshToken` (nullable `?string $clientSecret` to support both confidential + public clients) + `generatePkceVerifier` / `deriveS256Challenge`.
- ❌ **Deny-consent (RFC 6749 §4.1.2.1)** — Reclassified from §8.2 to §8.3 after flow analysis: the Cancel button in `scope-authorize.html.twig` is `window.history.back()` only and `AuthorizationController::authorizeUser` always approves — so there is no path for a user to formally refuse consent today. This is a spec-compliance gap (not a regression-gate gap) and needs a code change before a test can be added. Tracked in §8.3.
- **No new production code was added in either PR** — both completed items are regression-gate additions over currently-correct behavior.
- **New known limitations surfaced during implementation:** two edge cases (OIDC + `offline_access` refresh requiring explicit `scope=`, and OpenEMR DCR rejecting `token_endpoint_auth_method=none`) — recorded in §8.5.

#### V1/V2 scope-set api-tier gap (surfaced 2026-10-21 during PR #14749 review)

**What the server advertises:** `ServerScopeListEntity::getAllSupportedScopesList()` merges `apiScopes()` (V1 — `.read`/`.write`) + `getV2ApiScopes()` (V2 — compact CRUDS actionString like `user/allergy.crud` + `.rs`) + both FHIR v1/v2 forms. Both schemes are discoverable at DCR; the request-time check in `ScopeEntity::containsScope` + `createFromString()` resolves either shape against the endpoint's derived requirement (comment in `getV2ApiScopes` notes: *"createFromString() maps 'write' to create+update+delete and requires a v2 permission to be an ordered CRUDS substring, so 'cud' is what the endpoint's derived 'c'/'u'/'d' requirement resolves against"*).

**What the api test client mints:** `ApiTestClient::ALL_SCOPES` is **V1-flavored** — every standard-api scope is `.read`/`.write`. The only V2 forms in the list are two scopes added recently as exceptions: `user/insurance.$swap-insurance` (operation scope, V2-only by design) and `user/background_service.rs` + `user/background_service.$run` (also V2-only, added by #14749).

**Coverage reality:**

| Scheme × Direction | Isolated/unit | Api-tier end-to-end |
|---|---|---|
| V1 positive | ✅ `ScopePermissionObjectTest` v1-shortcut cases | ✅ Every passing api test confirms V1 scopes resolve correctly (whole suite runs under V1) |
| V1 negative — insufficient scope | ✅ `ScopePermissionObjectTest` ordering + permission-upgrade cases | ❌ **GAP** — no api test like "mint only `user/appointment.read`, call POST, assert 403" |
| V2 positive | ✅ `ScopePermissionObjectTest` CRUDS mapping, `ScopeEntityTest` v1→v2 expansion | ❌ **GAP** — no api test mints a V2-only token (e.g. `user/allergy.rs`) and hits a route. Exceptions: `BulkAPITestClient::SYSTEM_SCOPES_V2` for Bulk FHIR + `DisabledClientRejectionTest` passes `user/Patient.rs` through DCR + `G10_Certification/SinglePatient311APITest::testSingleApiRunWithV2Scopes` (Inferno-driven) |
| V2 negative — insufficient scope | ✅ CRUDS ordering tests | ❌ **GAP** |

**Risk:** The isolated-tier primitives (parse + containsScope + CRUDS) are solid. But a regression in the end-to-end wiring — anywhere between DCR-granted V2 scope → ScopeEntity on request → endpoint's required-scope resolution — would not be caught by the current api-tier suite. Example classes of silent regression:
- V2 token advertised and granted at DCR but silently dropped from access_token issuance
- Endpoint derivation picks `.c` from a POST route; `containsScope` fails to recognize `.cud` as satisfying it
- Context-match on `user/` vs `patient/` prefix for V2 forms

**Suggested shape:**

- **Audit-PR V2a — positive-match api gates:** mint a token with ONLY V2 scopes (`user/allergy.rs`, `user/allergy.cud`, `user/appointment.cruds`) and hit each read/write route. Assert equivalent access to the V1-scoped token. Can live in a new `ApiScopeSchemeV2EquivalenceTest` or extend existing per-resource test files via a data provider.
- **Audit-PR V2b — insufficient-scope deny gates:** mint tokens with specifically-narrow scopes (`user/appointment.r` only, no `.c`) and assert POST/PUT/DELETE return 403 (not 200, not 401). Mirrors the shape of the already-landed `OAuth2AttackScenarioTest` deny-path gates but at the resource-permission axis rather than the context-role axis. One test per permission axis is probably enough; this is bound-the-matrix work, not test-every-resource work.
- **Effort:** medium. The ApiTestClient's current credential-mint flow (`$credentials['scopes']`) already supports arbitrary scope lists, so no infrastructure work needed. ~20-30 tests across the two slices.

**Precedent for insufficient-scope deny coverage:** the `OAuth2AttackScenarioTest` covers role-mismatch denies (user token → portal route, patient token → standard-api route) and scope-downgrade on refresh. Resource-permission axis is a direct extension of the same pattern.

**Shipped 2026-10-21/22 (all merged, all rabbit-reviewed clean):**
- ✅ **V2a — PR #14760 MERGED 2026-10-22 at `222808f80044`:** `ApiScopeSchemeV2EquivalenceTest` — 7 tests, 108 assertions. Mints V2-only tokens across `.rs`, `.cud`, `.cruds`, `.crus`, `.r` permission axes on allergy / appointment / patient / insurance. Rabbit followup `668df7e05c` nested the tearDown so DB-persisted `site_addr_oath` + `oauth_password_grant` row restores run even if fixture/client cleanup throws.
- ✅ **V2b — PR #14759 MERGED 2026-10-22 at `da5f36ffeac3`:** `InsufficientScopeDenyApiTest` — 8 tests, 105 assertions. V1 and V2 permission-axis denies: `.read` can't POST, `.write` can't GET list (CUD lacks `.s`), `.rs` can't POST/PUT/DELETE, `.r` can't list, `.c` can't single-GET. Rabbit followup `594e0e4f40` catches `GuzzleHttp\Exception\ConnectException` in setUp and registers client ID before asserting token-response status (so a failed mint still gets cleaned up).
- **No real security findings in V2a/V2b** — every insufficient-scope mismatch was correctly rejected (401 via `AccessDeniedException` → `HTTP_UNAUTHORIZED`). Both PRs are regression-gate additions over correctly-behaving code.

#### Further black-spots audit (surfaced 2026-10-21 during V2a/V2b planning)

Classes of regression that the current test suite would miss, ordered by signal strength. Items 1 and 2 are flagged as the next audit targets after V2a/V2b (#14759 + #14760) land; items 3-8 are parked here for later. Each entry: shape of the gap, blast radius, suggested test shape.

**1. 🔥 ✅ CLOSED — Session-auth vs OAuth2 pathway parity (lesson from PR #14749).** Addressed via **PR #14762 MERGED 2026-10-22 at `b83914134d8c`**.

PR #14749 revealed that `/api/background_service/*` worked under session auth but 401'd every OAuth2 bearer-token caller for 7 months (March → October 2026). Root cause: `isLocalApi()` / `skipAuthorization` bypass `AuthorizationListener::onRestApiSecurityCheck` entirely, so the ACL gate works but the OAuth2 scope-registry gate never fires.

**Audit outcome:** systematic sweep of all 239 routes (96 standard-api + 136 FHIR + 5 portal + operations) via BOTH static-registry analysis AND live sweep against a running stack. **Zero dormant-OAuth2 reachability bugs on current master.** The #14749 incident was isolated. PR #14762 shipped a 6-line fix adding 12 missing FHIR `.read` scopes to `ApiTestClient::ALL_SCOPES` (test-client scope omission, not a prod bug — those scopes already existed in the registry).

**Architectural clarification the audit surfaced** (worth preserving): OpenEMR's REST API only accepts three auth paths:
1. **Bearer tokens** (OAuth2) — runs through `AuthorizationListener::onRestApiSecurityCheck`
2. **LocalApi** (`APICSRFTOKEN` header + session cookie, admin UI JS) — bypasses OAuth2 scope-check via `isLocalApi()` attribute
3. **SkipAuthorization** (metadata, well-known, OperationDefinition) — explicit opt-out

A plain `PHPSESSID` cookie never reaches a REST route — always 401s. The #14749 dormant class exists specifically where a route is reachable via LocalApi/skipAuthorization AND OAuth2's resource-scope check fails because the scope isn't registered. Full audit report at `/home/brady2/git/session-oauth2-parity-report.md` (129 lines, kept for future reference).

**2. 🔥 ✅ CLOSED — SMART v2 constraint-scope end-to-end wiring.** Addressed via **PR #14763 MERGED 2026-10-22 at `f82cadded3d5`** (6 tests, 134 assertions, 3 rabbit review rounds applied).

`ServerScopeListEntity::fhirResourceScopesV2()` advertises constrained scopes:
- `patient/Condition.rs?category=http://hl7.org/fhir/us/core/CodeSystem/condition-category|health-concern`
- `patient/Observation.rs?category=http://terminology.hl7.org/CodeSystem/observation-category|laboratory`
- `patient/DocumentReference.rs?category=http://hl7.org/fhir/us/core/CodeSystem/us-core-documentreference-category|clinical-note`

**Audit outcome:** full chain works as advertised. DCR cleanly accepts the constrained-scope strings. `finalizeScopes()` echoes the constraint verbatim in both the `scope` response field AND inside the JWT `scopes` claim (regression gate added for that). `ResourceConstraintFilterer` correctly admits matching-category resources and prunes non-matching ones at the FHIR endpoint. No wiring bugs. Covers 3 Condition tests (full admission+exclusion matrix across health-concern + problem-list-item + unconstrained-baseline), 2 Observation tests (vital-signs positive-admission + laboratory sibling-exclusion), 1 DocumentReference test (chain-integrity + per-entry category check).

**Deferred positive-admission for lab Observation + clinical-note DocumentReference** (surfaced via rabbit review on #14763, documented inline in the test file): the laboratory + clinical-note tests cover sibling-exclusion and chain-integrity but not positive-admission (empty bundle passes trivially). Both fixtures are heavy-lift: lab Observations are ingested through HL7 feeds not FHIR POST (would need direct DB inserts into `procedure_result` + `procedure_order` + encounter wiring), and `POST /fhir/DocumentReference` is explicitly `fhirWriteNotImplemented()` on master (would need direct DB inserts into `form_clinical_notes` + `documents` + encounter wiring). **Risk assessment: LOW** — services that return data typically regress to "wrong data" (caught by sibling-exclusion + Condition admission tests), not "always empty." Isolated-tier `ResourceConstraintFiltererTest` + `ScopeConstraintEnforcementWiringIsolatedTest` cover filterer logic directly. Chain-integrity gates in #14763 catch OAuth2/token-wiring regressions. Will close naturally when lab or clinical-note fixtures land for another purpose (bulk export, document template, etc). Non-blocking.

**Non-obvious finding worth preserving:** The three advertised Condition constraints use TWO different code systems:
- `health-concern` → `hl7.org/fhir/us/core/CodeSystem/condition-category` (us-core)
- `encounter-diagnosis` + `problem-list-item` → `terminology.hl7.org/CodeSystem/condition-category` (hl7 terminology)

SMART v2 constraints are system-sensitive `|` tokens — both halves must match. The agent initially coded `health-concern` with the hl7 terminology system and the filter correctly rejected it.

**3. DCR edge cases.**

- **Max redirect_uri count** — is there a documented limit? If not, a 10MB DCR payload with 1000 redirect_uris is a DoS surface at registration time.
- **Max scope count on registration** — DoS surface via massive scope requests
- **Unicode / punycode redirect_uri handling** — RFC 7591 implications; could an IDN-homograph attack register a redirect_uri that looks legitimate?
- **Duplicate client_name** — allowed? Deliberate? Does the response leak existence of other clients?
- **DCR response field completeness** — RFC 7591 lists required fields (`client_id_issued_at`, `grant_types`, `response_types`, `redirect_uris`, `token_endpoint_auth_method`, etc). An api test asserting all required fields are present would catch a silent drop.

Shape of audit: single file `tests/Tests/Api/DcrEdgeCaseApiTest.php`, ~8-10 tests, one per edge case.

**4. FHIR capability drift (follow-up on PR #14667).**

PR #14667 (`FhirSearchModifiersApiTest`) found 3 drifts between what `/fhir/metadata` CapabilityStatement advertises vs what the server actually serves (`_count` next-link not emitted, `_include` 404s, `_sort` silent no-op on non-Patient/Task). Precedent suggests more:
- Advertised `searchParam` entries that aren't actually implemented
- Advertised interactions (`conditional-create` via `If-None-Exist`, `conditional-update` via `If-Match`) that are advertised but 400'd or silently ignored
- Advertised search modifiers (`:not`, `:above`, `:below`, `:missing`) that are advertised but silently no-op'd

Shape of audit: generate the full CapabilityStatement at test time, iterate over each advertised feature, assert the server actually honors it. ~30-50 tests, likely data-provider-driven.

**5. OIDC claims structure in id_token.**

PR #14658 surfaced that `IdTokenSMARTResponse::getExtraParams` (OpenEMR's override) adds the `scope` field — this was non-obvious enough that rabbit asked about it incorrectly. No end-to-end test mints a token with `openid` + profile scopes, decodes the id_token, and asserts the claims match OIDC spec + discovery advertisement.

Shape of audit: new api test `OidcIdTokenClaimsApiTest.php`. Mint with various scope combinations, decode id_token, assert required claims per scope grant.

**6. Token revocation during active request.**

If an admin disables a client or revokes a token WHILE a long-running request is in flight (e.g. Bulk FHIR export status poll), does the server terminate the request, return a specific error, or complete as-if the token were still valid? Race condition; low-urgency but could matter for billing/audit integrity.

Shape of audit: time-coordinated test using async requests. Likely 1-2 tests.

**7. Response-type validation.**

`response_type=code` is standard and tested. What about:
- `response_type=token` (deprecated implicit flow — SHOULD be rejected per modern OAuth2)
- `response_type=id_token` (OIDC — may or may not be supported)
- Unknown response_type — RFC 6749 §4.1.2.1 requires `unsupported_response_type` error

Shape of audit: 3-5 tests in `OAuth2AttackScenarioTest`.

**8. Audit log integrity.**

Every OAuth2 token mint should produce an audit log entry (`log.event = 'oauth2-token-issued'` or similar). Every FHIR / standard-api call under an OAuth2 token should log client_id + scope + user. Are these actually written? Are they tested? Easy to silently drop during a refactor.

Shape of audit: 1-2 tests that mint a token, make a request, grep the log table for the expected entry.

**Status (2026-10-23):** Items 1, 2, 3, 5, 6, 7, 8 all closed (shipped or landed). Item 4 (FHIR CapabilityStatement drift) parked as its own subsection in §8.3 after a dedicated audit (14 drift classes documented; two-PR fix shape sketched; deferred). The deferred positive-admission gap for lab Observation + clinical-note DocumentReference is tracked under item 2.

**Shipped 2026-10-23 (items 3+5+6+7+8 dispatched in parallel):**
- ✅ **Item 3 — DCR edge cases, PR #14798 MERGED at `e5566394a12f`:** `DcrEdgeCaseApiTest` — 10 tests, 88 assertions. Covers RFC 7591 §3.2.1 response field completeness (contacts + scope echo on confidential + punycode round-trip on IDN accept branch per rabbit nit), payload-size edges (many redirect_uris, huge redirect_uri, massive scope string), duplicate client_name policy, IDN vs punycode handling, unsupported grant_types rejection. **Deferred (rabbit Actionable, not scope creep):** DCR response status is 200 on master; RFC 7591 §3.2.1 says 201 Created. A prod change belongs in its own PR with migration note for consumers assuming 200.
- ✅ **Item 5 — OIDC id_token claims, PR #14799 MERGED at `40c6a276b299`:** `OidcIdTokenClaimsApiTest` — 5 tests, 164 assertions. Covers OIDC Core §3.1.3.6 required claims (`iss`/`sub`/`aud`/`exp`/`iat`) + `aud === client_id` + `exp > time()` AND `exp > iat` + `sub` bound to seeded admin's UUID via `UserService::getUserByUsername('admin')` + `profile` scope surfacing `name`/`given_name`/`family_name`/`preferred_username` + `fhirUser` SMART claim ends with `/Person/{admin uuid}` + id_token signing alg pinned to `RS256` directly in addition to discovery-list agreement + verbatim nonce-echo (threaded nonce through `AuthCodeFlow::runFlow`). No wiring bugs.
- ✅ **Item 6 — Token revocation race, PR #14797 MERGED at `cfe1bbd6710f`:** `TokenRevocationRaceApiTest` — 2 tests, 23 assertions. Sequential revoke (freshly-minted token → GET→200 → UPDATE api_token SET revoked=1 → GET→401) + batched async revoke (4 getAsync + UPDATE + 5th getAsync). Both are regression gates on the fresh-DB-read invariant in `BearerTokenAuthorizationStrategy::authorizeRequest` → `AccessTokenRepository::isAccessTokenRevokedInDatabase`. Rabbit raised a worker-local cache concern; toned down the comment per Apache MPM/PHP-FPM distribution reality — test is not advertised as a per-worker cache detector.
- ✅ **Item 7 — response_type validation, PR #14795 MERGED at `be262a4bf581`:** 6 tests, 51 assertions added to `OAuth2AttackScenarioTest`. Covers `response_type=token` (implicit grant per RFC 9700 §2.1.2 forbidden), `=id_token`, `=foo` (unknown), `=` (empty), missing. All reject correctly. Plus a `response_type=code` positive control that proves the server reaches login/consent (addresses rabbit Major catch — URI mismatch between dcr-registered and authorize-sent URIs could have let tests pass vacuously; the control proves response_type is what's being validated).
- ✅ **Item 8 — Audit log integrity, PR #14796 MERGED at `0666c1e91541`:** `AuditLogIntegrityApiTest` — 3 tests, 49 assertions. Covers `api_log` row written on API call under OAuth2 bearer (carries client_id + user_id + method + request + request_url) + two separate audit rows per successful API call (BearerTokenAuthorizationStrategy "Token successfully used..." + ApiResponseLoggerListener on kernel.terminate) + failed password-grant writes a success=0 row carrying the attempted username. Bounded retry polling (20×50ms) around terminate-time writes to handle kernel.terminate commit timing. Rabbit followups applied: assertions bound to the specific probe call via api_log JOIN + lifecycle-based bearer-row binding + `user_id` assertion resolves and compares to the active admin's id (not just `assertGreaterThan(0)`).

**Bugs surfaced during these audits (moved to §8.3 as proper spec-compliance entries):**
- Authorize endpoint error-code drift (RFC 6749 §4.1.2.1 vs §5.2) — surfaced during #14795
- DCR response status 200 vs RFC 7591 §3.2.1's required 201 Created — surfaced during #14798 rabbit review
- OIDC discovery lives at non-canonical path (`/oauth2/default/.well-known/openid-configuration` vs OIDC Core 1.0's root) — surfaced during #14799
- Token-mint audit gap — mint moment not uniquely identifiable from `api_log` (blank client_id row); logging-trail integrity issue — surfaced during #14796

**Suggested next-up:** no urgent items. Options with current state:
1. **Item 4 — FHIR CapabilityStatement drift fixes** — 14 drift classes documented in §8.3; two-PR shape (HTTP-boundary error-shape hardening + FHIR search-param value validation) sketched. **Highest-impact remaining work** given breadth of surface + direct ONC-cert implications.
2. **§8.3 spec-compliance implementations** — `/userinfo` endpoint, `/revoke` endpoint, DCR PUT/DELETE, user-denial of consent. All medium-effort code changes with clear RFC compliance wins.
3. **Smaller follow-ups surfaced here:** authorize-endpoint error-code remap (unsupported_response_type), DCR response status 200→201 (RFC 7591), dedicated token-mint audit entry.

No critical gaps remain. The OAuth2/API pipeline is in strong shape — 0 real security findings across the 7 completed audits (items 1-8 minus item 4 deferred).

#### Next audit directions (surfaced 2026-10-24 after further-black-spots audit drained)

Items 1-8 fully drained. The following audits are the next-tier candidates, ranked by expected signal + lift. Pick based on current focus.

**🎯 Highest signal:**

1. **OAuth2 role-context symmetry audit** — natural follow-up to item 1 (session-vs-OAuth2 parity). Enumerate the 239 routes, probe each under user-role, patient-role, and system-role tokens (where applicable), diff reachability vs the context the route is designed for. `OAuth2AttackScenarioTest::testUserRoleTokenCannotReachPortalRoute` + `testPatientRoleTokenCannotReachStandardApiRoute` cover ONE axis each; the full matrix may have non-obvious gaps. Role-matrix enforcement in `AuthorizationListener::onRestApiSecurityCheck` lines 199-204 (the `isStandardApiRequest() && getRequestUserRole() !== 'users'` check) + `patient/*` vs `user/*` vs `system/*` scope prefix. Expected hits: not zero — role-matrix is complex enough that silent gaps are plausible. **Shape of audit:** report file + api test(s) covering the matrix. **If 0 bugs:** clean negative result like item 1. **If bugs:** likely genuine reachability issues worth fixing.
2. **Weak-assertion meta-audit** — rabbit has caught 3 "loose check doesn't match stated contract" patterns in the last 5 PRs (`user_id`, `sub`, `nonce`). Grep `tests/Tests/Api/` for `assertGreaterThan(0, ...)`, `assertNotSame('', ...)`, `assertMatchesRegularExpression('format', ...)`, `assertNotEmpty($array)` where the assertion message implies a specific identity/value. One pass likely yields 5-15 cleanups across the suite. Low cost per find, high regression-gate value.
3. **FHIR R4 search modifier regression gates** — lock current behavior per modifier before any §8.3 fix work. 9 modifiers to probe (`:not`, `:above`, `:below`, `:missing`, `:in`, `:not-in`, `:text`, `:contains`, `:exact`). 3 drifts already catalogued from PR #14667 + the §8.2 item 4 FHIR CapabilityStatement audit. NOT fixing the drifts — writing a dedicated regression-gate file that locks CURRENT behavior per modifier with explicit `markTestSkipped()` for the known drifts. Future prod fixes would then flip the skips to assertions.

**🔬 Medium signal:**

4. **PKCE deep coverage audit** — PR #14659 covered mint-level. Not yet covered: wrong-method PKCE (plain accepted on S256-marked clients?), tampered challenge at authorize, missing challenge at refresh, verifier-reuse across clients. PKCE is a common OAuth2 attack axis.
5. **Token endpoint rate-limit coverage symmetry** — PR #14577 covered password-grant. Not yet: client_credentials, refresh_token, auth_code mint. Does rate-limiting leak between clients?
6. **Scope-consent UX edge cases** — form double-submit, partial scope grants + downgrades combined, consent-form CSRF beyond what #14589 covers.
7. **OAuth2 token lifetime boundary coverage** — behavior at the moment of `exp` (clock-skew tolerance, `nbf` claim handling if present, 1-second-before vs 1-second-after `exp` symmetry).

**🌊 Larger / less focused:**

8. **Portal-API behavioral depth parity** — standard-api got deep coverage; portal has 5 routes with less depth-of-test. Edge cases, cross-patient isolation beyond #14734's precedent.
9. **CSRF coverage sweep on session-authenticated POST paths** — vast landscape; probably multi-session work.

**Current workstream (2026-10-24):** starting with 2 (weak-assertion meta-audit) as a warm-up, then 1 (role-context symmetry) as the full-matrix audit. 3-9 remain parked.

**Shipped 2026-10-24:**
- ✅ **Option 2 — Weak-assertion meta-audit, PR #14809 MERGED at `85f94425610b`:** Scanned 100 files under `tests/Tests/Api/`, found 3 real tightenings + ~130 legitimate-loose cases. Shipped as a single PR tightening the 3 cases. Report at `/home/brady2/git/weak-assertion-audit-report.md`. Hit rate low because rabbit already tightened the obvious OIDC/audit-log/nonce patterns during #14795-14799 — most remaining loose checks on server-generated values are the right check.
- ✅ **Option 1 — Role-context symmetry audit, PR #14810 MERGED at `37cde9145481`:** 15 tests, 122 assertions in new `RoleContextSymmetryApiTest.php` covering the full user-role × patient-role × system-role matrix across standard-api / portal / FHIR / bulk-export families. **Zero unexpected cross-role successes.** Role-matrix enforcement is consistent across all probed cells. Report at `/home/brady2/git/role-context-symmetry-report.md`. Non-obvious finding worth preserving: patient-role write-denial is a TWO-layer defense — Layer 1 (`ServerScopeListEntity::getV2ApiScopes()` has an explicit "No patient/ variant" comment + does not register any `patient/*.cud` entries) + Layer 2 (`AuthorizationListener::isPatientWriteRequest()` backstop at the dispatcher). In production Layer 1 always fires first because DCR refuses to grant the scope; Layer 2 is defense-in-depth. Both layers now locked with regression gates.

**Status (2026-10-24, items 1-9 progress):** Items 1 ✅, 2 ✅ closed. Items 3-9 remain parked. The 2026-10-24 pair (options 1 + 2) extends the "zero security findings" pattern — role-context enforcement is clean on master, and the weak-assertion meta-audit confirmed rabbit's catches in #14795-14799 were isolated incidents that caught the obvious cases.

### 8.3 Priority 3 — Spec compliance gaps

Endpoints/shapes the spec requires but OpenEMR has not implemented. No current security exposure, but ONC/OIDC auditors may flag.

- **`/userinfo` endpoint — unimplemented but advertised.** `src/RestControllers/Authorization/OAuth2DiscoveryController.php:71` emits `"userinfo_endpoint"` in discovery; `grep -rln 'userinfo' src/` finds only that one emit site + an unrelated SSRF validator. Any OIDC client that follows discovery and calls `/userinfo` gets a 404. **(Where: implement handler + add to router + api test. Effort: medium — new controller + repository method + tests. Risk: may surface OIDC claims-mapping bugs that cascade into more PRs.)**
- **`/revoke` endpoint (RFC 7009) — unimplemented, not advertised.** SMART configuration has a commented-out `revocation_endpoint`. **(Where: implement + tests + advertise. Effort: medium.)**
- **DCR PUT (update) + DELETE (deregister) — RFC 7592.** Only GET is implemented. PUT/DELETE return 200 as silent no-ops (no persist, no delete). Not a leak but a spec gap and a subtle integrity surprise for clients expecting these methods to be either functional or rejected. **(Where: `AuthorizationController::oauthClientRegistrationRead` sibling methods + tests. Effort: medium.)**
- **`invalid_request` vs. `invalid_grant` error label on auth-code reuse** — League 8.5.5 maps `isAuthCodeRevoked=true` to `invalid_request` in `validateAuthorizationCode`. RFC 6749 §5.2 specifies `invalid_grant` for a reused code. Fix bundled with the §8.1a auth-code validation-order DoS vendor-copy (same ~100 LoC fix in `CustomAuthCodeGrant::respondToAccessTokenRequest`). **(Effort: trivial increment on top of 8.1a fix.)**
- **User-denial of consent (RFC 6749 §4.1.2.1) — not implemented.** When a user denies consent at the authorization step, the server MUST redirect to the client's `redirect_uri` with `error=access_denied` (plus the state). OpenEMR's `AuthorizationController::authorizeUser` (lines ~1395-1439) always calls `$authRequest->setAuthorizationApproved(true)` before completing the authorization request, so a code is always issued. The `if ($request->request->has('proceed') ...)` branch only decides whether to call `saveTrustedUser` for future auto-approval; it does NOT short-circuit the code issuance. The Cancel button in `scope-authorize.html.twig` is a `window.history.back()` navigation only, never POSTs. In practice, no path currently exists for a user to formally refuse consent. **(Where: short-circuit `authorizeUser` when `proceed` is absent — build a 302 to the registered `redirect_uri` with `error=access_denied` + `state` instead of calling `completeAuthorizationRequest`. The TODO comment at line 1413 flags this. Add the UI cancel button to POST with a `deny=1` field or similar so users can trigger it. Effort: small — ~20 LoC + 2 api-tier tests.)**
- **Authorize-endpoint error code mislabeled (RFC 6749 §4.1.2.1)** — surfaced during PR #14795. The authorize endpoint emits `error=unsupported_grant_type` (token-endpoint error shape per §5.2) when `response_type` is invalid. §4.1.2.1 names `unsupported_response_type` as the authorize-endpoint-specific code. League's `OAuthServerException::unsupportedGrantType()` is the blanket shape used on both endpoints. **(Where: thin wrapper in `AuthorizationController` that remaps the error code when the request path matches the authorize endpoint, OR submit a league/oauth2-server upstream PR for a `unsupportedResponseType` factory. Effort: trivial — ~5 LoC + per-case assertions in `OAuth2AttackScenarioTest::testAuthorizeRejects*` (already shipped, assertions would just tighten).)**
- **DCR response status 200 vs RFC 7591 §3.2.1's required 201 Created** — surfaced during PR #14798 rabbit review. `AuthorizationController::clientRegistration` responds with HTTP 200 on successful registration; RFC 7591 §3.2.1 requires 201 Created. Not a leak or security issue — clients that follow the spec expect 201, clients that key off `data.client_id` presence work either way. **(Where: `AuthorizationController::clientRegistration` return path + update `DcrEdgeCaseApiTest` + `DcrClient::registerConfidential`/`registerPublic` helpers that currently assert 200. Effort: trivial — ~5 LoC prod + ~5 test adjustments. Risk: breaks any downstream client that strictly checks for `=== 200`. Add a migration note.)**
- **OIDC discovery at non-canonical path** — surfaced during PR #14799. OpenEMR serves discovery metadata at `/oauth2/default/.well-known/openid-configuration`. OIDC Core 1.0 §4 (Discovery) expects discovery at `${issuer}/.well-known/openid-configuration` with `issuer` matching the discovery URL. OIDC tooling that follows the spec may hit a 404. **(Where: add a route alias at `/oauth2/default/.well-known/openid-configuration` → canonical path (or re-root the canonical path), plus discovery `issuer` field alignment. Effort: small — alias + discovery metadata update + api test. Risk: already-integrated clients may have hard-coded the non-canonical path; alias both.)**
- **Token-mint moment not uniquely identifiable from `api_log`** — surfaced during PR #14796. The `/oauth2/.../token` POST writes an `api_log` row via `ApiResponseLoggerListener`, but `client_id` is blank because the request is pre-auth-check (the bearer-check hook that populates `client_id` runs on API calls under a bearer, not on the mint request itself). A forensic reader can't answer "when was this token minted?" from `api_log` alone — the first subsequent API call under the token writes `BearerTokenAuthorizationStrategy`'s "Token successfully used ..." entry which carries the client_id in the base64-encoded `log.comments`. Logging-trail integrity issue, not a security issue. **(Where: `AuthorizationController` token-endpoint success path — add a dedicated audit entry via `EventAuditLogger` naming the mint event + client_id + user_id. Effort: small — ~15 LoC + api-tier regression gate asserting mint produces a dedicated event. Risk: schema addition if a new event category is needed.)**

#### FHIR CapabilityStatement advertise-vs-serve drift inventory (2026-10-23 audit)

A systematic sweep audited every resource in `/fhir/metadata` against observed server behavior. 35 of 35 resources covered. 14 distinct drift classes found (several apply across most resources). The three search-modifier contract gaps already documented below are a strict subset of this inventory. Full tables with per-resource verdicts: `/home/brady2/git/fhir-capability-drift-report.md` (14.8KB). Technical detail on the two upstream-error-text leak endpoints: `/home/brady2/git/fhir-capability-drift-security-finding.md` (4.3KB).

**Bundle / search mechanics drifts (universal — apply to every resource):**

- **D1: `Bundle.total` reports paginated subset, not total matches.** Clients cannot know whether a result set is complete from the bundle metadata.
- **D2: No `next` / `previous` / `first` / `last` link emitted on any bundle.** `QueryPagination::getFhirLinks()` exists and knows how to emit the links, but no bundle-builder calls it. Clients cannot walk result sets via the FHIR-standard link-following mechanism. (Subset of this is the `_count` next-link gap from #14667.)
- **D3: `_include` advertised on 29+ resources, silently returns 200 with no included resources on every one tested.** Agent confirmed on multiple resources — generalizes the #14667 Encounter finding to the whole surface.
- **D4: `_revinclude` same shape as D3.** Advertised, returns 200 with no included resources.
- **D5: `_sort` on non-Patient/Task is a silent no-op.** `FhirServiceBase::searchForOpenEMRRecordsWithConfig()` discards the config. (Already in #14667 register below.)
- **D6: `_summary=count` / `_summary=true` not honored.** Returns the full bundle regardless.
- **D7: Unknown search params return `200 OK` with empty bundle on 12/13 probed resources.** Should return 400 BadRequest per FHIR R4 §3.1.1. Clients driving behavior from capability discovery get a wrong-but-plausible-looking empty result.
- **D8: Organization uniquely returns the FULL list on unknown search params instead of an empty bundle.** Filter-ignore pattern different from the other 12 resources — client that sends `?unknownParam=foo` on Organization gets ALL organizations unfiltered.
- **D9: `_elements` + `search.mode` not honored.** Various resources.

**OperationDefinition / routing drifts:**

- **D10: CapabilityStatement contains a duplicate `search-type` interaction entry** on some resources (observable in the metadata JSON).
- **D11: `delete` interaction advertised on some resources but no route handler** matches — hitting DELETE returns 405 or 404 depending on resource.
- **D12: `export` OperationDefinition is advertised but the `definition` reference does not resolve.** The CapabilityStatement points at an OperationDefinition URL that returns 404.
- **D13: Task `update` interaction advertised but not actually routed.** PUT on Task returns 405.

**Advertised search params that 5xx on non-matching values (7 endpoints):**

- `_id` with non-UUID value on CarePlan / Goal / Observation → 500 "UUID columns must be a valid UUID string" (exception message bleed into response body)
- `MedicationDispense?type=foo` → 500 "Call to undefined method FhirMedicationDispenseLocalDispensaryService::supportsCategory()" (class/method name leak)
- `Person?_revinclude=Provenance:target` → 500 "provenance record is not supported in this resource"
- `ValueSet?_count=1` → 500 "Expected string, number, or SearchField object for parameter '_config'"
- `Coverage?status=zzz` and `MedicationDispense?status=zzz` — response body includes the full raw SQL query the server attempted to execute, including table names, column names, and the full JOIN structure. Root cause: `vendor/adodb/adodb-php/drivers/adodb-mysqli.inc.php:1357` calls `outp_throw(..."SQL Statement failed on preparation: " . htmlspecialchars($sql) . "'", 'Execute')`; `ADOConnection::outp` echoes directly to stdout regardless of PHP's `display_errors` setting, so the text reaches the response buffer before Symfony's error handler can intercept. Additional observation: in the `status=zzz` case, the user-supplied FHIR search param is appended into the WHERE clause as a column reference (not as a value), producing the "Unknown column" error — a separate search-param parse layer issue worth fixing on its own merits.

**Suggested fix strategy (deferred):**

Two-PR shape when the team picks this up:

- **PR A — HTTP boundary error-shape hardening.** Wrap `FhirServiceBase::getAll` + sibling entry points in `try { ... } catch (\Throwable $e) { log + return OperationOutcome }`. Intercept BEFORE the ADODB echo path can reach the response buffer (via `ob_start` + Throwable catch + buffer discard on error path). Returns a generic `OperationOutcome` with no upstream message text, matches CLAUDE.md's standing rule: *"Never expose `$e->getMessage()` in user-facing output."* Includes regression gates asserting the 7 known-5xx endpoints now return generic OperationOutcome shape. ~30-50 LOC.
- **PR B — FHIR search-param value validation.** At `FhirServiceBase` or the search-param parse layer, validate values match their advertised FHIR type (`token` / `date` / `string`) before building the WHERE clause. Short-circuit to 400 BadRequest before reaching ADODB prepare. Specifically fixes the `?status=zzz` → column-reference drift noted above. Likely surfaces other search-layer bugs during implementation. Medium-large effort — one FhirServiceBase method change + per-service search-param registrations may need param-type metadata.

The bundle-mechanics drifts (D1-D9) are a separate workstream — each is a focused fix in `FhirResourcesService::createBundle()` or the resource-specific service, independent of the error-handling PRs. D7+D8 (unknown search-param behavior) and D11+D13 (advertised-but-not-routed interactions) are the highest-signal shippable fixes within that family.

**ADODB vendor patch NOT recommended:** OpenEMR has historically avoided vendor-code patches (maintenance tax, upstream drift). PR A + PR B together close the leak-to-client path without touching vendor code. ADODB echoing to stdout internally is still non-ideal but no longer reaches the response buffer once the HTTP boundary catch-all intercepts.

**Why deferred for now (2026-10-23):** Current focus is OAuth2/API pipeline audit expansion (items 3-8 of the §8.2 further-black-spots list). FHIR work will pick up after that register drains.

---

**FHIR search-modifier contract gaps (surfaced 2026-10-18 during PR #14667 audit).** The CapabilityStatement promises three modifiers that the implementation does not actually deliver. Documented inline in `tests/Tests/Api/FhirSearchModifiersApiTest.php` and listed here for tracking:

- **`_count` + Bundle `next` link — not implemented.** `_count=1` correctly returns `total=1` + a single entry, but `FhirResourcesService::createBundle()` only emits a `self` link. `QueryPagination::getFhirLinks()` exists and knows how to emit `first`/`previous`/`next`, but no bundle-builder calls it. Forward pagination via `link[rel="next"]` is effectively unsupported — a client cannot iterate past the first page via the FHIR-standard link-following mechanism. **(Where: wire `QueryPagination::getFhirLinks()` into the bundle-builder path; add an api test asserting `link[rel="next"]` presence + follow-and-recover-full-set invariant. Effort: small-medium.)**
- **`_include` — advertised but 404s with empty body.** Metadata advertises `searchInclude: ['*']` for every resource, but the search factory has no registration for the `_include` param name. `ResourceServiceSearchTrait::createSearchParameterForField` throws `SearchFieldException`, which `FhirServiceBase::getAll()` swallows into an empty bundle; the HTTP response is `404 Not Found` with a zero-entry Bundle body — verified by direct probe against Encounter. Clients that trust the CapabilityStatement and try `?patient={id}&_include=Encounter:patient` get an unexpected 404. **(Where: register `_include` as a search-factory param + wire resolution through the service layer; OR explicitly reject `_include` at the controller boundary with a clear `OperationOutcome` instead of a swallowed 404. Downgrade the CapabilityStatement claim to match. Effort: medium — architectural choice between "support it" and "advertise it off".)**
- **`_sort` on non-Patient/Task resources — silently a no-op.** `FhirServiceBase::searchForOpenEMRRecordsWithConfig()` is a pass-through to `searchForOpenEMRRecords()` that discards the config. Only `FhirPatientService` and `FhirTaskService` override it. Direct probe against Encounter: inserting 3 encounters with distinct dates returns them in insertion order from both `_sort=date` and `_sort=-date`. Clients can't tell the modifier was accepted-and-ignored. **(Where: propagate the sort config through the base-class search method; override on each FHIR service OR hoist the ordering into the base-class SQL builder. Scope the capability per resource until opt-in is complete. Effort: medium — ~15-20 services need the wiring. Regression gate for the opted-in Patient path landed via PR #14667.)**

#### Latent bugs surfaced during coverage work (consolidated register, as of 2026-10-19)

Non-test production bugs the testing-coverage sweeps uncovered that are **not yet fixed**. Each entry: where, what, consequence, suggested fix. The FHIR search-modifier contract gaps above are the three best-documented items; this register collects the rest so they don't rot in commit messages and memory records.

- ~~**`ListRestController::getAll` returns 404 for empty results**~~ (surfaced 2026-10-19 during PR #14680 CI diagnosis; FIXED 2026-10-19 by PR #14681 at `7dc1525903`). Mirrored PR #14605's `AppointmentRestController::listResponse()` pattern. api-tier regression gates in `ListRestControllerEmptyListTest` lock the empty-list invariant for medication/surgery/dental_issue.
- ~~**`/api/procedure` returns an SQL error page (HTML, not JSON) on master**~~ (surfaced 2026-10-18 during PR #14675 batch 1 scoping; FIXED 2026-10-20 by PR #14720 at `96a9a185cf`). Root cause was a dangling `LEFT JOIN` keyword pair in `ProcedureService::getAll()` SQL, accidentally left behind by PR #9357 ("Search handling for FHIR ServiceRequest", `7212a370981`) when a planned join was dropped. One-line removal + 2 api-tier regression gates in `ProcedureApiTest`.
- ~~**`InsuranceCompanyService::update()` references unset array keys without defaults**~~ (surfaced 2026-10-19 during PR #14680 PUT test attempt; FIXED 2026-10-20 by PR #14688 at `dc083555e6`). The service now fetches the existing row via `getOneById()` and overlays the submitted payload (true semantic PATCH); rabbit review surfaced two additional issues (address merge was still wiping unmentioned address fields, and `addressService->update()` returning null for a missing addresses row caused the whole update to fail silently) — both addressed by merging submitted address keys over `addressService->getOneByForeignId()` and falling through to `addressService->insert()` when the update finds nothing. Three api-tier regression gates added: partial-payload PATCH, address-merge PATCH, insert-when-missing. Also tightened `InsuranceCompanyService::$addressService` to `private readonly AddressService` (dropped two "method on mixed" baseline entries).
- ~~**`TransactionRestController::GetPatientTransactions` returns 404 for empty results**~~ (surfaced 2026-10-20 during PR #14689 scoping; FIXED 2026-10-20 by PR #14727 at `03ba739d7a`). Same empty-array-as-404 bug family as the already-fixed `AppointmentRestController` (PR #14605) and `ListRestController::getAll` (PR #14681). Dedicated empty-list regression gate added to `PatientTransactionApiTest`.
- ~~**`AppointmentRestController::getOne` ignores `$pid` on patient-compartment single-GET**~~ (surfaced 2026-10-20 during PR #14729 rabbit review; FIXED 2026-10-21 by PR #14734 at `fc1e82bb190e`). New controller helper `getOneScopedByPatient($pid, $eid)` mirrors `delete()`'s pattern — validates pid against the raw `AppointmentService::getAppointment($eid)` result before building the Response. Route closure now typed `string $pid, string $eid`. Regression gate `testGetAppointmentByIdForPatientRejectsWrongPatient` added to `AppointmentApiTest`. OpenAPI attribute moved off its stale location on `getOneForPatient()` onto the new method; swagger regenerated.
- ~~**`/api/background_service/*` routes 401 every OAuth2 caller (7-month OAuth2 dormancy)**~~ (surfaced 2026-10-21 during PR #14749 scoping; FIXED 2026-10-21 by PR #14749 at `c5d09ed9eb`). PR #11337 (Mar 2026, Michael A. Smith) shipped three routes with full OpenAPI docs and ACL gates but no OAuth2 scope registry — so `AuthorizationListener::onRestApiSecurityCheck` built required scopes `user/background_service.r/.s/.c` which did not exist in `ServerScopeListEntity`, and every OAuth2 call 401'd with "scope X not in access token". The `/:name/run` route additionally hit a parsing quirk: `HttpRestParsedRoute::parseRouteParams` takes `end($parts)` as the resource, so the required scope resolved to `user/run.c` (also nonexistent). Stephen Waite's PR #11687 established the `$run` naming convention with an inline comment flagging the parse quirk but did not retroactively fix the original `/run` route or register the scopes. Verified empirically against master — `ApiTestClient` probe produced `scope user/run.c not in access token` and `scope user/background_service.$run not in access token`. Likely testing path for the original author: session-authenticated UI calls (bypass `AuthorizationListener` via `isLocalApi()` / `skipAuthorization`). Fix: V2 scope registry (`user/background_service.rs` + `.$run`) + route rename `/:name/run` → `/:name/$run` + 6 api tests + sentinel-file probe fixture.
- ~~**Allergy, Condition, Insurance PUT routes ignore path puuid on compartment check**~~ (surfaced 2026-10-21 during PR #14734 systematic sweep + PR #14747 rabbit review; FIXED 2026-10-21 by PR #14747). Companion to the Appointment pid-scoping fix. Three different flavors in three services: (a) `AllergyIntoleranceService::update` + `ConditionService::update` had an `$expectedPatientId` hook but the controller let it stay `null` when the path puuid was malformed or named no patient — the service's compartment check only ran on non-null, so the UPDATE bound pid from the row's own owning pid and the mutation succeeded through any crafted path (rabbit catch). (b) `InsuranceService::update` had no compartment hook at all — WHERE was keyed on uuid alone. Fix: controller-level reject-when-null for allergy + condition; service-level pid-match pre-check for insurance. 5 api-tier regression gates (3 wrong-pid + 2 unknown-puuid).

Items in this register should either (a) get fixed with their own PR, (b) have api-tier regression gates added as part of the fix PR, or (c) be promoted to §8.1a / §8.3 if they rise to a security or spec-compliance concern.

### 8.4 Priority 4 — Feature gaps

None of the §8 items are purely new-feature gaps independent of spec compliance. The §8.3 spec items double as feature additions but are classified by their spec-mandate posture, not feature-wishlist posture.

### 8.5 Known limitations / documented trade-offs (not gaps per se)

Carried forward in case a future PR wants to revisit:

- **Auth-code validation-order DoS** — awaiting upstream `league/oauth2-server` interface addition. Local workaround (atomic-claim in `AuthCodeRepository::isAuthCodeRevoked`) correctly enforces single-use but creates a pre-validation code-burn window. Impact is low (attacker gains nothing by burning a code they already hold; victim cost is one re-authorize click). Tracked in §9 for upstream coordination.
- **`invalid_request` vs. `invalid_grant` error label on auth-code reuse** — same root cause as above. League 8.5.5 maps `isAuthCodeRevoked=true` to `invalid_request`; RFC 6749 §5.2 specifies `invalid_grant`. Fix lives in the same upstream interface change tracked in §9.
- **No SHA pin on external-client smokes.** Rolling master pulls. Transient breakage expected; triage first checks whether the regression is in OpenEMR or the external repo. Documented in `smart-launch-test.yml` header comment.
- **Inferno signal-swallow bug** — ✅ CLOSED (see §8.2 entry; `ci/inferno/run.sh` fix landed and both residuals pass cert as of 2026-10-17 status audit).
- **OIDC + `offline_access` refresh requires explicit `scope=` on the refresh exchange.** When a flow requests `openid` + `offline_access` + resource scopes and the OIDC `nonce` claim is in session, `ScopeRepository::finalizeScopes()` appends a synthetic `nonce` scope to the issued-and-bound set (OIDC id_token nonce handling, src/Common/Auth/OpenIDConnect/Repositories/ScopeRepository.php:175-181). The `nonce` artifact is bound to the refresh token, and the refresh exchange's "every bound scope must be a known scope" check then fails with `invalid_scope` / "Check the nonce scope" because `nonce` is not a registered scope. Workaround (also what Inferno does): always pass an explicit `scope=` on the refresh exchange naming only real scopes — RFC 6749 §6-compliant and avoids the artifact. Not a bug per se (the artifact drives id_token nonce echoing on access-token issuance) but worth documenting so a caller doesn't assume the "omit scope to reuse bound set" branch works for every grant shape. Covered by `tests/Tests/Api/RefreshTokenScopePersistenceTest.php` (test includes an inline note tying the explicit-scope requirement back to this limitation).
- **OpenEMR DCR rejects `token_endpoint_auth_method=none`.** Per RFC 7591 the `none` value signals a public client. OpenEMR's `AuthorizationController::registerClient` allowed-list restricts the field to `client_secret_basic | client_secret_post | private_key_jwt` (src/RestControllers/AuthorizationController.php:294). The flip to a public client in OpenEMR is driven by `application_type != 'private'` instead — with `application_type=web` the server skips `client_secret` generation, so the registered client is effectively public even though `token_endpoint_auth_method` is declared as `client_secret_post`. New public-client DCR callers should follow the `AuthorizationGrantPublicClientPkceTest` registration pattern (and the `DcrClient::registerPublic` helper). Public clients may also only request `patient/*` scopes — `user/*` / `system/*` are rejected at DCR time with `invalid_client_metadata` (AuthorizationController.php:348-353). Covered end-to-end by `tests/Tests/Api/PublicClientPkceMintTest.php`.

---

## 9. Upstream `league/oauth2-server` coordination plan

Two items from §8 are architecturally better addressed in `thephpleague/oauth2-server` rather than in OpenEMR's local fork of behavior:

1. **Race-safe single-use enforcement on authorization codes** (§8.1a resolution).
2. **Correct `invalid_grant` label on code reuse** (§8.5).

Both resolve to the same root cause: league's `AuthCodeRepositoryInterface` design has a race window between its two methods, and `revokeAuthCode(): void` cannot signal duplicate claims. Any implementer wanting proper single-use enforcement under concurrency faces a trade-off between (a) putting the claim in `isAuthCodeRevoked` (what OpenEMR currently does — correct under concurrency but burns codes pre-validation, see §8.5), (b) overriding `AuthCodeGrant::respondToAccessTokenRequest` wholesale (~100 LoC vendor-copy with ongoing maintenance tax), or (c) accepting the race.

### Phase 1 — File upstream issue (short-term, lightweight)

**Status:** draft ready (below). File against `thephpleague/oauth2-server` at the issue tracker as a standalone concern, not a security advisory. The underlying issue is a public interface limitation discussed openly; no attack content, no OpenEMR-specific disclosure.

**Draft issue:**

> ---
>
> **Title:** `AuthCodeRepositoryInterface` cannot signal duplicate-claim without vendor-copying `AuthCodeGrant::respondToAccessTokenRequest`
>
> **Body:**
>
> RFC 6749 §4.1.2 and OIDC Core 1.0 §3.1.3.5 both require authorization codes to be single-use. Implementers using `league/oauth2-server`'s `AuthCodeRepositoryInterface` face a race window and an interface limitation when enforcing this under concurrent exchanges.
>
> ### The race
>
> `AuthCodeGrant::respondToAccessTokenRequest` (`src/Grant/AuthCodeGrant.php`) calls:
>
> 1. `AuthCodeRepositoryInterface::isAuthCodeRevoked($codeId)` → read-only check inside `validateAuthorizationCode`.
> 2. (validate redirect_uri / PKCE / scopes.)
> 3. `issueAccessToken(...)` + `issueRefreshToken(...)`.
> 4. `AuthCodeRepositoryInterface::revokeAuthCode($codeId)` → `void`, called last.
>
> Two concurrent exchanges of the same code can both pass step 1 before either reaches step 4. Both receive tokens. In 8.5.5 the `revokeAuthCode` call at the end is void, so there's no way for the second exchange to detect that it lost the race.
>
> ### The interface limitation
>
> Making `isAuthCodeRevoked` do an atomic claim (SELECT-with-write-side-effect) closes the race by using a DB primary-key constraint as the serializer. But it has to fire BEFORE league validates redirect_uri / PKCE / scopes (which happen later in `respondToAccessTokenRequest`), so a client with a wrong redirect_uri or wrong `code_verifier` burns the code pre-validation. For benign clients this means one re-authorize cycle after a config typo. For adversarial callers who already hold a leaked code, it's a no-gain DoS vector but it's a vector nonetheless.
>
> The alternative — override `respondToAccessTokenRequest` wholesale and insert the atomic claim between validation and token issuance — requires copying ~100 lines of the parent method (which uses the `private validateAuthorizationCode` method, so a clean inheritance split isn't possible). This locks implementers into a vendor-copy that has to be rebased against every upstream release.
>
> ### Proposed interface additions
>
> Two options:
>
> **A. Return a boolean from `revokeAuthCode` and call it earlier.**
>
> ```php
> interface AuthCodeRepositoryInterface
> {
>     // Change return type void → bool. True = claim succeeded; false = duplicate.
>     public function revokeAuthCode($codeId): bool;
> }
> ```
>
> Then in `AuthCodeGrant::respondToAccessTokenRequest`, call `revokeAuthCode` after validation but before token issuance:
>
> ```php
> // ... existing validation (validateAuthorizationCode, validateCodeChallenge, etc.) ...
>
> if ($this->authCodeRepository->revokeAuthCode($authCodePayload->auth_code_id) === false) {
>     throw OAuthServerException::invalidGrant('Authorization code has already been used');
> }
>
> // Issue tokens
> $accessToken = $this->issueAccessToken(...);
> ```
>
> This also lets the grant throw the RFC-correct `invalid_grant` on reuse rather than the current `invalid_request` emitted by `validateAuthorizationCode` when `isAuthCodeRevoked` returns true.
>
> **B. Add a sibling method with explicit atomic-claim semantics.**
>
> ```php
> interface AuthCodeRepositoryInterface
> {
>     // Returns true if the claim succeeded (code is now consumed);
>     // false if the code was already claimed by a prior exchange.
>     public function claimAuthCode($codeId): bool;
> }
> ```
>
> `AuthCodeGrant::respondToAccessTokenRequest` would call `claimAuthCode` instead of the current `isAuthCodeRevoked` + `revokeAuthCode` pair. The existing two methods could be kept as deprecated aliases for backwards compatibility.
>
> ### Backwards compatibility
>
> Option A is a breaking change to the interface signature (void → bool). Option B is additive if the existing methods are retained as deprecated.
>
> Happy to prototype a PR for whichever option the maintainers prefer.
>
> ---

### Phase 2 — Draft PR (future, when there is time)

Prerequisites to scoping a PR:

1. **Decide between option A (breaking) or option B (additive).** Option B is safer for an established library; option A is cleaner API-wise. Likely the maintainers' call.
2. **Reference implementation**: write a stub `AuthCodeRepositoryInterface::claimAuthCode` (or modified `revokeAuthCode`) + update `AuthCodeGrant::respondToAccessTokenRequest` + add deprecation shim for the old pair.
3. **Test coverage upstream**: league/oauth2-server has its own test suite under `tests/`. The PR would need:
   - Unit test on the new interface method
   - Integration test showing single-use enforcement under simulated concurrency (two sequential exchanges of same code, second fails with `invalid_grant`)
   - Deprecation-shim test if going option B
4. **Migration note for implementers**: README / UPGRADE docs for the new method.

Estimated PR size: ~150-250 LoC including tests + docs. Not large by upstream standards but needs careful discussion of the backwards-compat shape before submission.

### Phase 3 — OpenEMR adopts upstream

Once upstream ships the new interface:

1. Update `src/Common/Auth/OpenIDConnect/Repositories/AuthCodeRepository.php` to use the new method. Remove the atomic-claim-via-`isAuthCodeRevoked` workaround (`isAuthCodeRevoked` goes back to pure read).
2. The code-burn window at §8.5 closes naturally — the claim now fires post-validation.
3. The `invalid_request` → `invalid_grant` label gap at §8.5 also closes.
4. Remove both entries from §8.5. Add a note to `AuthCodeRepository` referencing the upstream migration.

Both OpenEMR §8.5 trade-offs resolve in one upstream release.

---

## 10. Appendix — file paths worth bookmarking

- `tests/README.md`, `tests/Tests/README.md`, `tests/Tests/WebUi/README.md` — existing test-suite docs.
- Config: `phpunit.xml`, `phpunit-isolated.xml`, `phpstan.neon.dist`, `.phpstan/extension.neon`, `semgrep.yaml`.
- CI workflows: `.github/workflows/{test.yml,isolated-tests.yml,integration-tests.yml,inferno-test.yml,phpstan.yml,semgrep.yml,api-explorer-test.yml,smart-launch-test.yml,acceptance-only.yml,acceptance-docker.yml,acceptance-package.yml}`.
- Orchestrators: `ci/inferno/run.sh`, `ci/smart-launch/run.sh`, `ci/api-explorer/{run.sh,driver.php,seed-globals.sql}`, `ci/ciLibrary.source`.
- Custom PHPStan rules: `tests/PHPStan/Rules/` (21 rules).
- Attack-suite shape: `tests/Tests/Api/OAuth2AttackScenarioTest.php`.
- Shared fixtures: `tests/Tests/Fixtures/PortalPatientFixtureManager.php`.
- Auth-code single-use fix (PR #14531): `src/Common/Auth/OpenIDConnect/Repositories/AuthCodeRepository.php` + table `oauth_auth_code_revocations` in `sql/{8_4_1-to-8_5_0_upgrade.sql,database.sql}` (v_database 550).
- CORS per-client fix (PR #14527): `src/Common/Auth/OpenIDConnect/CORSAllowedOriginRegistry.php` + `src/RestControllers/Subscriber/CORSListener.php`.
- Grant classes: `src/Common/Auth/OpenIDConnect/Grant/Custom{AuthCode,Password,ClientCredentials,RefreshToken}Grant.php`.
- Repositories: `src/Common/Auth/OpenIDConnect/Repositories/{AuthCode,AccessToken,RefreshToken,Client,Scope,User}Repository.php`.
