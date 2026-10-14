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

Non-FHIR api tests in `tests/Tests/Api/`:

| File | Count |
|---|---|
| `AppointmentApiTest.php` | 4 |
| `DocumentApiTest.php` | 15 |
| `FacilityApiTest.php` | 7 |
| `HealthEndpointTest.php` | 3 |
| `InsuranceCompanyApiTest.php` | 5 |
| `InsuranceEmployerSearchApiTest.php` | 9 |
| `ListApiTest.php` | 2 |
| `PatientAllergyConditionApiTest.php` | 5 |
| `PatientApiTest.php` | 7 |
| `PractitionerApiTest.php` | 7 |
| `PrescriptionApiTest.php` | 9 |
| `ProductApiTest.php` | 1 |
| `VersionApiTest.php` | 1 |

### 6.3 /apis/default/fhir/* coverage

Covered in §5.3 — per-resource `Fhir*ApiTest.php` files. Routes without dedicated per-route tests include write paths for less common resources + bulk-data OperationDefinition endpoints.

### 6.4 /apis/default/portal/* coverage

**No `tests/Tests/Api/*Portal*ApiTest.php` files.** Portal-adjacent tests exist at service layer only:

- `tests/Tests/Services/AdminUnblockCounterResetTest.php` (14)
- `tests/Tests/Services/Portal/*` — various.
- Portal auth path reached indirectly via `PasswordGrantPatientRoleFlowTest.php` (3, #14531).

### 6.5 Acceptance (release-artifact smoke)

Covered in §3.2 under Acceptance.

### 6.6 External-client api smoke

`sjpadgett/oe-module-api-explorer` via `.github/workflows/api-explorer-test.yml`:

- **DCRs four grant configurations** (confidential auth-code, public PKCE, JWT client-credentials, SMART EHR launch).
- **Driver** (`ci/api-explorer/driver.php`) exercises **one** path end-to-end: JWT `client_credentials` → `fhir/Patient` → assert `Bundle` with `total >= 1`.
- Other three grants are registered but not driven.
- No write-path smoke, no pagination, no `_include`/`_revinclude`, no portal routes.

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
- **`state` CSRF deny-paths** — **PARTIAL (Phase B).** `OAuthLoginCsrfRejectionTest::testOAuthLoginPostWithMissingCsrfTokenIsRejected` added. Replayed/tampered-after-step-1 variants still pending.
- **PKCE deny-paths beyond happy** — **PARTIAL (Phase B).** `AuthorizationGrantPublicClientPkceTest::testPublicClientRejectedAtAuthorizeWithoutPkce` + `testPublicClientRejectsPkceMethodDowngradeAttempt` added. Verifier-mismatch variant still pending (noted in `AuthorizationGrantPublicClientPkceTest`).
- **JWKS malformed / empty keys / null keys at JWT-assertion time (api layer)** — **CLOSED (Phase B).** `JwksBoundaryNegativesTest::testJwtAssertionAgainstClientWithMalformedJwksJsonIsRejected` + `testJwtAssertionAgainstClientWithEmptyKeysArrayIsRejected` + `testJwtAssertionAgainstClientWithNullKeysIsRejected` added. The malformed-JSON and null-keys cases surfaced a production hygiene fix: `JsonWebKeySet::__construct` called `property_exists` on `json_decode`'s null return (throws `TypeError` → HTTP 500) and `foreach` on `$jwks->keys = null` (emits warning → `ErrorException` under the repo error handler). Guard now `!is_object($jwks) || !property_exists($jwks, 'keys') || !is_array($jwks->keys)`; obsolete PHPStan baseline entries for the mixed-property access removed.
- **CORS `Access-Control-Allow-Origin: *` + `Access-Control-Allow-Credentials: true` invariant assertion** — **CLOSED (Phase B).** `OAuth2AttackScenarioTest::testCorsResponseDoesNotCombineWildcardAllowOriginWithCredentials` drives both an unauthenticated request and an authenticated bearer request from a client whose registered redirect_uri base matches the Origin header.
- **Rate-limit / brute-force at HTTP layer** — **CLOSED via PR #14577 (follow-up).** `PasswordGrantRateLimitHttpTest::testTokenEndpointRejectsCorrectPasswordAfterBruteForceThresholdReached` — snapshots `password_max_failed_logins`, `ip_max_failed_logins`, `oauth_password_grant`, admin `users_secure` row, and the request-IP `ip_tracking` row; sentinel-mints a successful token to prove credentials work; then two wrong-password attempts bring the per-account counter to threshold; the third attempt with the CORRECT password is still rejected while the lockout window is active. Six-commit history exercised the full snapshot/restore + capture-flag + setUp-rollback pattern needed for api-layer tests mutating shared `globals` + counter rows.

### 8.2 Priority 2 — Testing (coverage expansion + layer symmetry)

Tests that improve regression signal without changing production behavior.

**In-flight external contributor PRs** chip at this register — status collected 2026-10-13. These are open on `openemr/openemr`; coordination hint on each. Column key: ✅ CI green • ⚠️ transient / infra failure not caused by diff • 🚫 CI gated (contributor's workflow runs not yet triggered — usually first-time-contributor approval or codecov) • 👁️ reviewers.

| PR | Author | Shape | CI | Reviewers | Lane in this register |
|----|--------|-------|----|-----------|-----------------------|
| [#14194](https://github.com/openemr/openemr/pull/14194) FHIR OperationDefinition api tests | namespaceMarcello | test-only (196/0, 2 files) | ✅ 75 green, 1 pending | — | Resource-level FHIR api coverage; also exercises **authorization skip list** (adjacent to our §8.1b dispatcher work) |
| [#14195](https://github.com/openemr/openemr/pull/14195) FHIR Procedure api tests | namespaceMarcello | test-only (561/0, 1 file) | ✅ 71 green, 1 pending | 👁️ coderabbit | Resource-level FHIR api coverage |
| [#14219](https://github.com/openemr/openemr/pull/14219) FHIR Group api tests | namespaceMarcello | test-only (418/0, 2 files) | ⚠️ Upgrade-from-5.0.0/setup failed (infra, not diff) | 👁️ coderabbit | Resource-level FHIR api coverage |
| [#14190](https://github.com/openemr/openemr/pull/14190) ValueSet `_id` → `list_options.option_id` | namespaceMarcello | prod fix + api tests (406/4, 3 files) | ✅ 75 green, 1 pending | 👁️ coderabbit | FHIR resource prod fix + api coverage |
| [#14218](https://github.com/openemr/openemr/pull/14218) Specimen honors `status` search param | namespaceMarcello | prod fix + api tests (562/5, 3 files) | ✅ 75 green, 1 pending | 👁️ coderabbit | FHIR resource prod fix + api coverage |
| [#14220](https://github.com/openemr/openemr/pull/14220) Media reports deleted docs as entered-in-error | namespaceMarcello | prod fix + api tests (422/1, 3 files) | ✅ 73 green, 1 pending | — | FHIR resource prod fix + api coverage |
| [#14292](https://github.com/openemr/openemr/pull/14292) Dead refresh token → 400 invalid_grant | namespaceMarcello | prod fix + isolated test (197/1, 3 files) | ✅ 73 green, codecov patch/project failing (test-code excluded, not blocking) | — | Grant-handler lane; semantically adjacent to our OAuth2AttackScenarioTest |
| [#14511](https://github.com/openemr/openemr/pull/14511) Ignore duplicate consent submissions | shwetanktomar | prod fix + isolated test (56/0, 2 files) | 🚫 full matrix not yet triggered (2/2 checks only) | — | AuthorizationController; same file our PKCE/CSRF tests exercise from outside |
| [#14516](https://github.com/openemr/openemr/pull/14516) Complete server scope catalog types | sn1thy | prod + isolated test (142/149, 9 files) | 🚫 full matrix not yet triggered (2/2 checks only) | — | ScopeRepository + AuthorizationController; low direct overlap |

**Coordination hints:** Several of these PRs modify `tests/Tests/Api/ApiTestClient.php` — merge-order favors our PR #14574 (does not touch it). Consistent helper naming (`assertOAuthUnauthorizedResponse`) is worth preserving across the series.

- **Inferno signal-swallow bug** (`project_inferno_ci_signal_swallow`). `ci/inferno/run.sh` wraps phpunit in an if-conditional that disables `set -e`, so cert-side failures have historically shown green. Residuals: Bulk Group Compartment Export + Smoking Status Observations. **(Where: `ci/inferno/run.sh`. Effort: small — 5-line bash fix. Highest signal-per-effort of any item in this register because it unblocks the 31 existing cert tests.)**
- **Portal API surface** — 5 routes, zero api tests. `PortalPatientFixtureManager` is already available. **(Where: `tests/Tests/Api/*PortalApiTest.php`. Effort: medium.)**
- **EHR launch end-to-end via a real client.** Standalone launch is covered by `oe-smart-questionnaire` smoke; EHR launch (user clicks SMART app from inside OpenEMR → `launch=<launch_param>` → authorize) is only covered at the isolated layer (`EhrLaunchPatientContextTest`). **(Where: add EHR-launch mode to smart-launch smoke, or new smoke with an EHR-launch-capable test app. Effort: medium-large. External dependency: needs an EHR-launch-capable test app — either extend `oe-smart-questionnaire` or find an alternate.)**
- **Public client (no secret) PKCE from an external client.** `AuthorizationGrantPublicClientPkceTest` has 3 api tests, but no external-client smoke drives it. **(Where: add a public-client lane to `oe-module-api-explorer` driver. Effort: small-medium.)**
- **Backend-services (`system/*`) SMART launch from an external client.** Scope-set exercised by cert tests only. **(Where: extend `api-explorer` driver with full `$export` round-trip including status polling + NDJSON retrieval. Effort: medium.)**
- **Password + auth-code + refresh grants via a real third-party client.** `api-explorer` DCRs them but doesn't drive them. **(Where: extend `ci/api-explorer/driver.php`. Effort: small each path.)**
- **FHIR writes from a real third-party client.** The only external-client smoke that touches FHIR is `api-explorer` → `GET fhir/Patient`. No POST/PUT/DELETE smoke. **(Where: extend driver.php. Effort: small-medium.)**
- **Pagination / `_sort` / `_include` / `_revinclude` smoke.** Not exercised by external-client smokes. **(Where: extend driver.php or add dedicated api tests. Effort: medium.)**
- **Scope-narrowing on consent.** User flips a consent checkbox off, token's scope should be narrower. No test covers this. **(Where: api test driving `/scope-authorize-confirm` with partial scope list. Effort: medium.)**
- **Error-path browser flows in SMART launch.** Denied-consent, expired launch token, missing patient context — no end-to-end coverage. **(Where: extend `SmartLaunchQuestionnaireTest`. Effort: medium.)**
- **`ScopeConstraintFilterer` direct isolated test** — no direct isolated test (only wiring test); twin `ResourceConstraintFilterer` has 10 direct isolated tests. **(Where: new isolated test. Effort: small.)**
- **`IntrospectionTest` isolated split** — 32 api tests, no isolated test on the controller internals. **(Where: isolated-tier split. Effort: medium.)**
- **SMART authorization controller api coverage** — 14 isolated tests, no api-layer coverage of consent-screen HTML output beyond `AuthorizationGrantFlowTest`'s implicit coverage. **(Where: api test rendering `/scope-authorize-confirm` + asserting known markers. Effort: medium.)**
- **FHIR Bulk broader positive coverage** — 1 api test (negative), 1 cert test (positive), rich service-layer. Spec surface is wider. **(Where: `GroupExportFhirApiTest` + new api tests. Effort: medium. Note: the `system/*` scope-downgrade deny side is Priority 1a above.)**

### 8.3 Priority 3 — Spec compliance gaps

Endpoints/shapes the spec requires but OpenEMR has not implemented. No current security exposure, but ONC/OIDC auditors may flag.

- **`/userinfo` endpoint — unimplemented but advertised.** `src/RestControllers/Authorization/OAuth2DiscoveryController.php:71` emits `"userinfo_endpoint"` in discovery; `grep -rln 'userinfo' src/` finds only that one emit site + an unrelated SSRF validator. Any OIDC client that follows discovery and calls `/userinfo` gets a 404. **(Where: implement handler + add to router + api test. Effort: medium — new controller + repository method + tests. Risk: may surface OIDC claims-mapping bugs that cascade into more PRs.)**
- **`/revoke` endpoint (RFC 7009) — unimplemented, not advertised.** SMART configuration has a commented-out `revocation_endpoint`. **(Where: implement + tests + advertise. Effort: medium.)**
- **DCR PUT (update) + DELETE (deregister) — RFC 7592.** Only GET is implemented. PUT/DELETE return 200 as silent no-ops (no persist, no delete). Not a leak but a spec gap and a subtle integrity surprise for clients expecting these methods to be either functional or rejected. **(Where: `AuthorizationController::oauthClientRegistrationRead` sibling methods + tests. Effort: medium.)**
- **`invalid_request` vs. `invalid_grant` error label on auth-code reuse** — League 8.5.5 maps `isAuthCodeRevoked=true` to `invalid_request` in `validateAuthorizationCode`. RFC 6749 §5.2 specifies `invalid_grant` for a reused code. Fix bundled with the §8.1a auth-code validation-order DoS vendor-copy (same ~100 LoC fix in `CustomAuthCodeGrant::respondToAccessTokenRequest`). **(Effort: trivial increment on top of 8.1a fix.)**

### 8.4 Priority 4 — Feature gaps

None of the §8 items are purely new-feature gaps independent of spec compliance. The §8.3 spec items double as feature additions but are classified by their spec-mandate posture, not feature-wishlist posture.

### 8.5 Known limitations / documented trade-offs (not gaps per se)

Carried forward in case a future PR wants to revisit:

- **Auth-code validation-order DoS** — awaiting upstream `league/oauth2-server` interface addition. Local workaround (atomic-claim in `AuthCodeRepository::isAuthCodeRevoked`) correctly enforces single-use but creates a pre-validation code-burn window. Impact is low (attacker gains nothing by burning a code they already hold; victim cost is one re-authorize click). Tracked in §9 for upstream coordination.
- **`invalid_request` vs. `invalid_grant` error label on auth-code reuse** — same root cause as above. League 8.5.5 maps `isAuthCodeRevoked=true` to `invalid_request`; RFC 6749 §5.2 specifies `invalid_grant`. Fix lives in the same upstream interface change tracked in §9.
- **No SHA pin on external-client smokes.** Rolling master pulls. Transient breakage expected; triage first checks whether the regression is in OpenEMR or the external repo. Documented in `smart-launch-test.yml` header comment.
- **Inferno signal-swallow bug** — tracked above under §8.2 as the small-but-high-signal fix.

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
