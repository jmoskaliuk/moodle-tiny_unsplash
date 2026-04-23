# Compliance Checklist — tiny_unsplash (Unsplash · Pexels · Pixabay)

This plugin integrates three external image APIs. Each provider has its own
rules; the table below summarises what the plugin enforces and what
administrators / authors are responsible for.

## Per-provider checklist

| Topic                          | Unsplash       | Pexels         | Pixabay                                  |
|--------------------------------|----------------|----------------|------------------------------------------|
| Hotlinking allowed             | yes            | **yes**        | **NO** — must download & self-host       |
| Attribution required           | recommended    | **required**   | not required (but recommended)           |
| Rate limit (default)           | 50 / hour      | 200 / hour     | 100 / 60 s                               |
| Mandatory cache                | no             | no             | **≥ 24 h** (enforced in `base_client`)   |
| API key location               | server config  | server config  | server config                            |
| API key sent to browser        | yes (legacy)   | **NO**         | **NO**                                   |
| Outbound call origin           | browser        | Moodle server  | Moodle server                            |
| Stored metadata                | per `<figure>` | per `<figure>` | per stored_file (`author`, `license`, `source`) |

## What the plugin does for you

- [x] API keys for Pexels and Pixabay live in `tiny_unsplash` config and are
  only read inside the server-side proxy (`classes/external/`).
- [x] All outbound HTTP for Pexels / Pixabay goes through Moodle's `curl`
  wrapper with a 15s timeout, retry-with-exponential-backoff on `429` / `5xx`,
  and respects `Retry-After` headers.
- [x] Search responses are cached via the Moodle Cache API
  (`db/caches.php` definition `apiresults`).
- [x] The Pixabay client floors `cachettl` at 24 h in code, regardless of admin
  setting (see `pixabay_client::minimum_cache_ttl`).
- [x] Pixabay images are downloaded into the user's draft area via the Moodle
  File API; the file's `author`, `license` and `source` (provider page URL)
  fields are populated automatically.
- [x] Search query parameters are filtered against an explicit whitelist before
  leaving the Moodle server (defence against accidental data leaks).
- [x] Capability `tiny/unsplash:use` is enforced on both web service endpoints
  and at editor configuration time.
- [x] Privacy provider declares all three APIs as external locations and
  exposes the `searchquery` field name to the privacy registry.

## What administrators must verify

- [ ] Each provider's free-tier registration is completed and Terms of Use are
  accepted by your institution.
- [ ] If you operate inside the EU/EEA: review whether forwarding search
  queries to a US-based provider needs to be disclosed in your privacy policy.
- [ ] Rate limits suit your traffic — the cache TTL admin setting can be
  raised (it is floored at 24 h for Pixabay only).
- [ ] The cache store backing `tiny_unsplash/apiresults` has enough room (set
  in **Site administration → Plugins → Caching → Configuration**).

## What authors / teachers must remember

- [ ] When the **Show attribution** site setting is on, each inserted image
  ships with a `<figcaption>` linking the photographer and the source page.
  Do not remove it for Pexels images (attribution is requested by their TOS).
- [ ] Pixabay images become a normal Moodle file once inserted — they are
  redistributable inside the course, but **may not** be re-published as a
  standalone image library outside Moodle.
- [ ] Do not paste personally identifying data into the search box. The query
  is forwarded to the chosen provider.

## DSGVO / GDPR notes

- The plugin does not log search queries; only the Moodle web service
  invocation is logged at the standard Moodle web service log level.
- No user identifier is forwarded to the upstream APIs.
- Pixabay's "no hotlinking" policy aligns well with GDPR — once downloaded,
  the asset is served from the Moodle origin, so end users' browsers do not
  contact pixabay.com on every page view.
- Pexels and Unsplash hotlinks DO cause the end user's browser to contact the
  provider's CDN. If that is unacceptable for your data-protection scope,
  switch to "Download & insert" via a future UI extension or disable those
  providers by leaving the API key blank.
