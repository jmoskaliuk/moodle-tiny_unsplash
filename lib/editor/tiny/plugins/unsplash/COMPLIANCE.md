# Compliance Checklist — tiny_unsplash (Unsplash · Pexels · Pixabay)

This plugin integrates three external image APIs. **All providers use the same
flow:** server-side search via Moodle web service proxy → server-side download
into the user's draft area via the Moodle File API → embedding as a normal
draftfile URL. There is no hotlinking path — neither the search nor the final
`<img src>` reach a third-party host.

## Per-provider checklist

| Topic                          | Unsplash       | Pexels         | Pixabay                                  |
|--------------------------------|----------------|----------------|------------------------------------------|
| Insert mode                    | download       | download       | download                                 |
| Hotlink path exposed           | **no**         | **no**         | **no**                                   |
| Attribution rendered           | yes (UTM)      | yes            | yes                                      |
| Rate limit (default plan)      | 50 / hour      | 200 / hour     | 100 / 60 s                               |
| Mandatory cache                | no             | no             | **≥ 24 h** (enforced in `base_client`)   |
| API key location               | server config  | server config  | server config                            |
| API key sent to browser        | **NO**         | **NO**         | **NO**                                   |
| Outbound call origin           | Moodle server  | Moodle server  | Moodle server                            |
| Stored metadata                | per stored_file (`author`, `license`, `source` = provider page URL) |||

## What the plugin does for you

- [x] **Zero API keys in the browser.** All three keys live in `tiny_unsplash`
  config and are only read inside the server-side classes (`classes/api/*`,
  `classes/external/*`).
- [x] All outbound HTTP goes through Moodle's `curl` wrapper with a 15s
  timeout, retry-with-exponential-backoff on `429` / `5xx`, and respects
  `Retry-After` headers.
- [x] Search responses are cached via the Moodle Cache API
  (`db/caches.php` definition `apiresults`).
- [x] The Pixabay client floors `cachettl` at 24 h in code, regardless of
  admin setting (see `pixabay_client::minimum_cache_ttl`).
- [x] Every selected image is downloaded into the user's draft area; the
  file's `author`, `license` and `source` (provider page URL) fields are
  populated automatically.
- [x] Unsplash's `download_location` tracking endpoint is hit on insert (per
  Unsplash API guidelines) — server-side, also via `curl`.
- [x] Search query parameters are filtered against an explicit whitelist
  before leaving the Moodle server (defence against accidental data leaks).
- [x] Capability `tiny/unsplash:use` is enforced on both web service endpoints
  and at editor configuration time.
- [x] Privacy provider declares all three APIs as external locations and
  exposes the `searchquery` field name to the privacy registry.

## What administrators must verify

- [ ] Each provider's free-tier registration is completed and the institution
  accepts the provider's Terms of Use.
- [ ] Storage budget: every inserted image becomes a real file in your
  Moodle filesystem. Sizing should account for that.
- [ ] Rate limits suit your traffic — the cache TTL admin setting can be
  raised (it is floored at 24 h for Pixabay only).
- [ ] The cache store backing `tiny_unsplash/apiresults` has enough room (set
  in **Site administration → Plugins → Caching → Configuration**).

## What authors / teachers must remember

- [ ] When the **Show attribution** site setting is on, each inserted image
  ships with a `<figcaption>` linking the photographer and the source page.
  Do not remove it for Pexels images (attribution is required by their TOS).
- [ ] Stock images become a normal Moodle file once inserted — they are
  redistributable inside the course, but **may not** be re-published as a
  standalone image library outside Moodle.
- [ ] Do not paste personally identifying data into the search box. The query
  is forwarded to the chosen provider.

## DSGVO / GDPR notes

- The plugin does not log search queries; only the Moodle web service
  invocation is logged at the standard Moodle web service log level.
- No user identifier is forwarded to the upstream APIs.
- Because there is no hotlinking, end users' browsers never contact any
  provider CDN. Every `<img src>` resolves to a `pluginfile.php` /
  `draftfile.php` URL on the Moodle origin.
