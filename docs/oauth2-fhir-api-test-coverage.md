# OpenEMR Test Coverage — OAuth2, SMART-on-FHIR, FHIR, and REST API

**Audience:** maintainers (human and AI). **Purpose:** reference landscape of what is tested, where, how it runs, and where the gaps are, so future contributors can target gaps without re-researching the whole surface each time.

**Base state:** post-PR #14531 (merge SHA `ff1a129a86`, 2026-10-13). All file paths are relative to the repo root. Test counts reflect the state at that merge; later landings add to the picture but shouldn't invalidate the structure of this document.

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

Ordered loosely by priority (biggest-structural gaps first; nits last). Each entry notes **(impact, where to add, rough effort)**.

### 8.1 Positive-testing gaps (happy paths not exercised end-to-end)

- **`/userinfo` endpoint — unimplemented but advertised.** `src/RestControllers/Authorization/OAuth2DiscoveryController.php:71` emits `"userinfo_endpoint"` in discovery; `grep -rln 'userinfo' src/` finds only that one emit site + an unrelated SSRF validator. Any OIDC client that follows discovery and calls `/userinfo` gets a 404. **(Impact: OIDC conformance gap. Where: implement handler + add to router + api test. Effort: medium — new controller + repository method + tests.)**
- **`/revoke` endpoint (RFC 7009) — unimplemented, not advertised.** SMART configuration has a commented-out `revocation_endpoint`. **(Impact: SMART App Launch 2.0 recommends; some clients expect it. Where: implement + tests + advertise. Effort: medium.)**
- **DCR PUT (update) + DELETE (deregister) — RFC 7592.** Only GET is implemented. PUT/DELETE return 200 as silent no-ops (no persist, no delete). Not a leak but a spec gap. **(Impact: RFC 7592 conformance. Where: `AuthorizationController::oauthClientRegistrationRead` sibling methods + tests. Effort: medium.)**
- **EHR launch end-to-end via a real client.** Standalone launch is covered by `oe-smart-questionnaire` smoke; EHR launch (user clicks SMART app from inside OpenEMR → `launch=<launch_param>` → authorize) is only covered at the isolated layer (`EhrLaunchPatientContextTest`). **(Impact: EHR launch is the primary SMART use case for an EHR vendor. Where: add EHR-launch mode to smart-launch smoke, or new smoke with an EHR-launch-capable test app. Effort: medium-large.)**
- **Public client (no secret) auth-code via PKCE from an external client.** `AuthorizationGrantPublicClientPkceTest` has 3 api tests, but no external-client smoke drives it. **(Impact: real-world SMART mobile apps are public clients. Where: add a public-client lane to `oe-module-api-explorer` driver. Effort: small-medium.)**
- **Backend-services (`system/*`) SMART launch from an external client.** Scope-set exercised by cert tests only. **(Impact: SMART Backend Services is the ONC-mandated bulk-export flow. Where: add to one of the smokes. Effort: medium.)**
- **Portal API surface** — 5 routes, zero api tests. **(Impact: patient-portal clients hit these. Where: `tests/Tests/Api/*PortalApiTest.php`. Effort: medium — needs portal patient fixture, already provided by `PortalPatientFixtureManager`.)**
- **Password + auth-code + refresh grants via a real third-party client.** `api-explorer` DCRs them but doesn't drive them. **(Impact: catches real-client API-shape drift. Where: extend `ci/api-explorer/driver.php`. Effort: small.)**
- **Scope-narrowing on consent.** User flips a consent checkbox off, token's scope should be narrower. No test covers this. **(Impact: SMART consent UX. Where: api test driving `/scope-authorize-confirm` with partial scope list. Effort: medium.)**
- **Error-path browser flows in SMART launch.** Denied-consent, expired launch token, missing patient context — no end-to-end coverage. **(Impact: SMART UX robustness. Where: extend `SmartLaunchQuestionnaireTest`. Effort: medium.)**
- **FHIR writes from a real third-party client.** The only external-client smoke that touches FHIR is `api-explorer` → `GET fhir/Patient`. No POST/PUT/DELETE smoke. **(Impact: write-path contract drift. Where: extend driver.php. Effort: small-medium.)**
- **Pagination / `_sort` / `_include` / `_revinclude` smoke.** Not exercised by external-client smokes. **(Impact: FHIR search profile conformance. Where: extend driver.php or add dedicated api tests. Effort: medium.)**

### 8.2 Negative / deny-path gaps

- **Bulk FHIR `system/*` scope downgrade.** `GroupExportFhirApiTest` has one non-existent-group deny; the actual "token has `system/Patient.read` but attempts `system/Group.$export`" scope-downgrade attack is uncovered. Explicitly deferred to "post-PR landing" per Brady 2026-10-13. **(Impact: bulk-export authorization. Where: `OAuth2AttackScenarioTest` or new file + Group/Patient `$export` deny-paths. Effort: medium.)**
- **Cross-scope lateral escalation on bearer presentation.** `BearerTokenRejectionPropertiesTest::testTokenWithReadOnlyScopeCannotWriteViaFhirEndpoint` is one case. Patient→user→system lateral attempts not surfaced as a full matrix. **(Impact: scope-boundary enforcement. Where: `OAuth2AttackScenarioTest` + `BearerTokenRejectionPropertiesTest`. Effort: medium.)**
- **Token-endpoint race on authorization code across worker processes.** `testSecondExchangeOfSameCodeIsRejected` proves single-process serialization via DB unique constraint; `ClientRegistrationReadTest::testTwoConcurrentReadsWithSameTokenCannotBothSucceed` already uses `curl_multi` for DCR read. **No matching curl_multi test for the token endpoint.** **(Impact: validates the atomic-claim fix under real concurrency. Where: new api test using curl_multi. Effort: small.)**
- **ID token (OIDC) tampering through the auth-code flow.** SMART launch token tampering + logout `id_token_hint` tampering are covered. ID-token-at-authentication tampering through the auth-code → id_token issuance → client-side verify path is not. **(Impact: id_token integrity. Where: new api test or extend `AuthorizationGrantFlowTest`. Effort: small-medium.)**
- **`state` CSRF deny-paths beyond the one existing test.** `OAuthLoginCsrfRejectionTest` is 1 method. Missing/replayed/tampered-after-step-1 variants not tested. **(Impact: CSRF robustness. Where: `OAuthLoginCsrfRejectionTest`. Effort: small.)**
- **PKCE deny-paths beyond happy.** `AuthorizationGrantPublicClientPkceTest` is 3 methods. Full matrix (verifier mismatch × missing × wrong-method × S256-vs-plain downgrade) not exhausted. **(Impact: PKCE conformance. Where: `AuthorizationGrantPublicClientPkceTest` + attack suite. Effort: small-medium.)**
- **JWKS SSRF / unreachable / malformed at JWT-assertion time (api layer).** `AuthorizationControllerJwksUriValidationIsolatedTest` has 17 isolated tests at DCR registration time. `JwksBoundaryNegativesTest` has 3 at api layer. The "DCR with SSRF-bait `jwks_uri`, prove token endpoint refuses to resolve it" api-layer path is thin. **(Impact: SSRF protection on token exchange. Where: extend `JwksBoundaryNegativesTest`. Effort: small.)**
- **CORS `Access-Control-Allow-Origin: *` + credentials bad pairing** — the attack-scenario suite doesn't explicitly assert this invariant; the semgrep rule guards source-side introduction. **(Impact: belt-and-suspenders. Where: `OAuth2AttackScenarioTest` CORS section. Effort: small.)**
- **Rate-limit / brute-force at HTTP layer.** `PasswordGrantHardeningTest` has 38 service-layer tests on `AuthUtils` counters; no api-layer test asserts the HTTP response shape when a client hits the rate limit via `/token`. **(Impact: documents the brute-force deny-path contract. Where: new api test. Effort: small.)**

### 8.3 Coverage asymmetries between layers

- **`ScopeConstraintFilterer`** — no direct isolated test (only wiring test); twin `ResourceConstraintFilterer` has 10 direct isolated tests. **(Impact: untested branch logic. Where: new isolated test. Effort: small.)**
- **`IntrospectionTest`** — 32 api tests, no isolated test on the controller internals. **(Impact: slower feedback loop. Where: isolated-tier split. Effort: medium.)**
- **SMART authorization controller** — 14 isolated tests, no api-layer coverage of consent-screen HTML output beyond `AuthorizationGrantFlowTest`'s implicit coverage. **(Impact: consent UI drift. Where: api test rendering `/scope-authorize-confirm` + asserting known markers. Effort: medium.)**
- **SMART launch e2e** — 1 Panther test vs. 8 isolated SMARTLaunchToken + 3 EhrLaunchPatientContext + 2 SMARTSessionTokenContext. **(Impact: e2e is thinnest. Where: extend `SmartLaunchQuestionnaireTest` with error paths + EHR launch mode. Effort: medium-large.)**
- **FHIR Bulk** — 1 api test (negative), 1 cert test (positive), rich service-layer. Spec surface is wider. **(Impact: Bulk Data IG conformance. Where: `GroupExportFhirApiTest` + new api tests. Effort: medium.)**

### 8.4 Known limitations / documented trade-offs

Carried forward in case a future PR wants to revisit:

- **Auth-code validation-order DoS trade-off** (deferred by Brady 2026-10-13 during #14531 review). `AuthCodeRepository::isAuthCodeRevoked` does the atomic claim BEFORE league validates redirect_uri/PKCE. A wrong-redirect-uri exchange burns the code pre-validation (DoS: attacker who holds the code prevents legitimate exchange, but can't mint tokens). Proper fix requires ~100 LoC league vendor-copy in `CustomAuthCodeGrant::respondToAccessTokenRequest`.
- **`invalid_request` vs. `invalid_grant` error label on auth-code reuse.** League 8.5.5 maps `isAuthCodeRevoked=true` to `invalid_request` in `validateAuthorizationCode`. RFC 6749 §5.2 specifies `invalid_grant` for a reused code. Fix lives in the same vendor-copy as the DoS trade-off above.
- **Inferno signal-swallow bug** (`project_inferno_ci_signal_swallow`). `ci/inferno/run.sh` wraps phpunit in an if-conditional that disables `set -e`, so cert-side failures have historically shown green. Residuals: Bulk Group Compartment Export + Smoking Status Observations.
- **No SHA pin on external-client smokes.** Rolling master pulls. Transient breakage expected; triage first checks whether the regression is in OpenEMR or the external repo.

---

## 9. Appendix — file paths worth bookmarking

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
