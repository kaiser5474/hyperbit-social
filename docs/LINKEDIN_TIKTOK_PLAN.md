# Plan: LinkedIn + TikTok en HyperBit Social

**Fecha:** 25 nov 2026 · **Repo:** kaiser5474/hyperbit-social (público) · **Sitio:** social.hyperbitit.com
**Estado:** solo investigación y plan. No se escribió código, no se crearon cuentas ni apps, no se tocó el repo.
**Objetivo de Jarvis:** dejar de usar Buffer y programar sus vídeos desde su propia herramienta. Hoy Facebook Pages funciona (texto + fotos), Instagram tiene código sin probar en vivo, Threads es solo un stub, y **no existe nada de LinkedIn ni de TikTok**.

---

## 1. Resumen ejecutivo

| | LinkedIn (página de empresa) | TikTok |
|---|---|---|
| **API oficial** | Posts API (`POST /rest/posts`) + Videos API | Content Posting API (Direct Post) |
| **Para publicar vídeo en la página/cuenta** | Producto **Community Management API** (revisión manual de LinkedIn) + scope `w_organization_social` | Producto **Content Posting API** + scope `video.publish` + **auditoría de TikTok** aprobada |
| **Revisión/auditoría** | Sin plazo publicado por LinkedIn — planificar **semanas** | Sin plazo publicado por TikTok — planificar **semanas** |
| **Esfuerzo de código (con Cursor)** | ~12–18 h | ~12–18 h |
| **Esfuerzo de Jarvis (trámites)** | ~2–3 h (crear app, verificar empresa, enviar revisión) | ~2–3 h (crear app, grabar demo, enviar auditoría) |
| **Atajo mientras llega la aprobación** | Ninguno para la página (solo publicar a mano en linkedin.com) | **Upload-to-inbox** (`video.upload`): funciona sin auditoría, pero alguien tiene que terminar cada post en la app de TikTok |

**Recomendación: LinkedIn primero.** Es donde van sus vídeos (la página de HyperBit es el canal principal y su canal de ventas nº 2 es LinkedIn), su caso encaja limpio en los requisitos de LinkedIn (HyperBit IT es una LLC registrada, justo lo que piden), y TikTok tiene el riesgo mayor: su auditoría puede rechazar una herramienta de uso puramente personal (ver §7, riesgo nº 1). Además, la revisión de LinkedIn es lenta y sin plazo: cuanto antes se envíe, antes llega. TikTok se hace después, con el atajo de Upload-to-inbox como victoria rápida.

**Tiempo total de calendario realista:** el código de ambas redes son ~35–50 h repartidas en fases, pero el calendario lo mandan las revisiones: **4–8 semanas** hasta tener las dos redes publicando en vivo (si las revisiones pasan a la primera).

**Prerrequisito común (Fase 0): la herramienta hoy NO acepta vídeos.** `StorePostRequest` solo permite imágenes (`jpg,jpeg,png,gif`, máx 10 MB). Los vídeos de Jarvis son MP4 de ~50 MB. Sin Fase 0 no hay ni LinkedIn ni TikTok.

---

## 2. Cómo funciona hoy HyperBit Social (mapa de la arquitectura)

Leído del repo (commit `003f1cf`, 23 nov 2026). Stack: Laravel 12, PHP 8.3, MySQL, Tailwind 4 + Alpine, Meta Graph API v22.0. Sin Redis ni workers: un cron cada minuto ejecuta `schedule:run`.

### 2.1 El flujo de publicación (lo que las redes nuevas deben enchufar)

```
Usuario crea Post (content + media + scheduled_at + canales)
        │
        ▼
posts:publish-due  (app/Console/Commands/PublishDuePosts.php, cada minuto vía routes/console.php)
  → Post::dueForPublishing()  (solo status=scheduled y scheduled_at <= now)
  → por cada PostChannel → PublisherManager::publish()
        │
        ▼
PublisherManager (app/Services/Publishers/PublisherManager.php)
  → busca el primer Publisher cuyo supports($account) sea true
        │
        ▼
Publisher (implementa app/Contracts/PublisherInterface)
  supports(SocialAccount): bool
  publish(PostChannel): PublishResult
  getPlatformName(): string
        │
        ▼
PublishResult: success(platformPostId, permalink)
             | failure(message, code)
             | pending(containerId, status)  ← para flujos asíncronos
```

**Esto es lo bueno: el diseño ya es multi-red.** Añadir LinkedIn/TikTok es crear dos clases nuevas que implementen `PublisherInterface` y registrarlas en `PublisherManager` + `MetaServiceProvider`. No hay que tocar el comando, el scheduler ni el flujo de estados.

### 2.2 Archivos clave que ya existen (y qué hace cada uno)

| Archivo | Rol | Relevancia para LinkedIn/TikTok |
|---|---|---|
| `app/Contracts/PublisherInterface.php` | Contrato: `supports()`, `publish()`, `getPlatformName()` | **Lo implementan** `LinkedInPublisher` y `TikTokPublisher` |
| `app/Contracts/PublishResult.php` | `success` / `failure` / `pending(containerId, status)` | Ya soporta flujos asíncronos — LinkedIn (procesado de vídeo) y TikTok (estado de publicación) lo reutilizan |
| `app/Services/Publishers/PublisherManager.php` | Inyecta los 3 publishers, resuelve por cuenta, lista plataformas | Añadir los 2 nuevos al constructor y a `getSupportedPlatforms()` |
| `app/Services/Publishers/FacebookPagePublisher.php` | Publica texto/foto en la Page vía Graph API | Patrón a copiar (try/catch `MetaApiException`, `markAsUsed()`, `markError()`, logs) |
| `app/Services/Publishers/InstagramPublisher.php` | Contenedor IG: crea → espera `FINISHED` (poll 10×3 s) → publica | Patrón del flujo asíncrono a copiar; su `MAX_POLL_ATTEMPTS`/`POLL_INTERVAL_SECONDS` es el modelo para el poll de LinkedIn/TikTok |
| `app/Services/Publishers/ThreadsPublisher.php` | Stub que devuelve `NOT_IMPLEMENTED` | Ejemplo de cómo se deja una red "próximamente" |
| `app/Services/Meta/MetaGraphClient.php` | Cliente HTTP Laravel (base `graph.facebook.com`, v22.0, retry 2×, `handleResponse` lanza `MetaApiException`) | Patrón a copiar en `LinkedInApiClient` y `TikTokApiClient` (cada uno con su base URL, sus headers y su manejo de errores) |
| `app/Models/SocialAccount.php` | `platform` (constantes `facebook_page`, `instagram_business`, `threads`), `platform_id`, `access_token` **encriptado** (Crypt), `token_expires_at`, `parent_page_id`, `is_active`, `last_used_at`, `last_error` | Añadir constantes `linkedin_organization` y `tiktok`; **falta columna `refresh_token`** (ver §2.4) |
| `app/Models/Post.php` | `content`, `media_path`, `media_type`, `media_url`, `scheduled_at`, `timezone`, estados `draft/scheduled/publishing/published/failed/cancelled`, `MAX_RETRIES = 3`, `hasMedia()`, `getPublicMediaUrl()` | Sin cambios de esquema; solo la validación de subida debe aceptar vídeo |
| `app/Models/PostChannel.php` | Pivot post↔cuenta: `status`, `platform_post_id`, `permalink`, `error_message/code`, `container_id`, `container_status` | `container_id` se reutiliza para guardar el URN del vídeo de LinkedIn y el `publish_id` de TikTok |
| `app/Console/Commands/PublishDuePosts.php` | Procesa posts vencidos, marca éxitos/fracasos/parciales | **No se toca**; pero hace falta un comando nuevo para re-chequear pendientes (ver §2.4) |
| `app/Http/Controllers/Auth/FacebookController.php` | OAuth login con Facebook (también es el login de usuario) | Patrón a copiar en `Auth/LinkedInController.php` y `Auth/TikTokController.php` — pero solo para **conectar cuentas**, no para login |
| `app/Http/Controllers/AccountController.php` | `connect()` lista Pages (+ IG vinculada), `store()` guarda tokens de página, `refresh()` renueva token de página | Patrón a copiar: `connect()` de LinkedIn lista organizaciones; el de TikTok guarda `open_id` + tokens |
| `app/Http/Controllers/PostController.php` | `store()` guarda media en disco `public`, crea `PostChannel`s; `publishNow()` reutiliza `PublisherManager` | `store()` debe aceptar vídeo; `publishNow()` ya sirve para las redes nuevas sin cambios |
| `app/Http/Requests/StorePostRequest.php` | Validación: `content` máx 63206, `media` solo imágenes ≤10 MB, `channels` obligatorios | **Debe cambiar**: aceptar `mp4,mov` y subir el límite (ver Fase 0) |
| `config/meta.php` / `config/services.php` | Credenciales Meta por `.env` | Añadir `config/linkedin.php` y `config/tiktok.php` con el mismo patrón |
| `routes/web.php` / `routes/console.php` | Rutas `/auth/facebook/*`, `/accounts/*`, `/posts/*`; `posts:publish-due` cada minuto | Añadir `/auth/linkedin/*`, `/auth/tiktok/*` y `posts:check-pending` |
| `app/Providers/MetaServiceProvider.php` | Singletons: `MetaGraphClient`, los 3 publishers, `PublisherManager` | Registrar los 2 clientes y 2 publishers nuevos |
| `database/migrations/2024_01_01_00000{1,2,3}_*.php` | `social_accounts`, `posts`, `post_channels` | Nueva migración: `refresh_token` + `refresh_token_expires_at` en `social_accounts` |
| `tests/Feature/FacebookPublisherTest.php`, `InstagramPublisherTest.php` | Tests con `Http::fake()` | Mismo patrón para `LinkedInPublisherTest` y `TikTokPublisherTest` |
| `resources/views/accounts/connect.blade.php` | Lista de Pages con checkbox `fb_{id}` / `ig_{id}_{pageId}` | Añadir secciones de LinkedIn (organizaciones) y TikTok (cuenta) |

### 2.3 Qué ya resuelve el diseño actual (no reinventar)

- **Programación:** ni LinkedIn ni TikTok tienen "programar" nativo en su API. No hace falta: el cron `posts:publish-due` publica a la hora exacta. Es el mismo modelo que ya funciona con Facebook.
- **Flujos asíncronos:** `PublishResult::pending()` + `container_id` ya existen por Instagram. LinkedIn (el vídeo tarda en procesarse) y TikTok (la publicación tarda en completarse) encajan ahí.
- **Tokens encriptados:** `SocialAccount::accessToken()` usa `Crypt` — los tokens de LinkedIn/TikTok se guardan igual.
- **Multi-cuenta por post:** `PostChannel` ya permite un post en varias cuentas; un vídeo puede ir a la página de LinkedIn y a TikTok a la vez con el mismo `Post`.

### 2.4 Huecos que hay que cerrar (afectan a las dos redes)

1. **No existe subida de vídeo.** `StorePostRequest` rechaza todo lo que no sea imagen. Y el límite de 10 MB no sirve para vídeos de ~50 MB.
2. **No existe columna `refresh_token`.** TikTok caduca el `access_token` cada **24 h** (sin refresh no hay publicación programada posible) y LinkedIn cada **60 días**. Hace falta migración: `refresh_token` (encriptado) + `refresh_token_expires_at` en `social_accounts`, y un comando `tokens:refresh` (diario) que renueve los de TikTok (y los de LinkedIn si la app llega a tenerlos).
3. **Nadie re-chequea los pendientes.** `posts:publish-due` solo recoge posts en estado `scheduled`. Cuando un canal queda `pending` (contenedor IG, vídeo LinkedIn en proceso, publicación TikTok en proceso), nada lo vuelve a mirar: el post se queda en `publishing` para siempre. Hace falta un comando nuevo `posts:check-pending` (cada 2–5 min) que pregunte el estado y marque publicado/fallido. Instagram hoy se salva porque su publisher espera hasta 30 s dentro del propio `publish()`; con vídeos de 50 MB eso no basta.
4. **El login de usuario ES Facebook.** `User` solo tiene `facebook_id`/`facebook_token`. LinkedIn y TikTok no sustituyen el login: son flujos de "conectar cuenta" aparte, con sus propios controladores y callbacks. No tocar `FacebookController`.
5. **Límites del hosting compartido (Hostinger).** Subir vídeos de 50 MB choca con `upload_max_filesize` / `post_max_size` de PHP (en compartido suelen ser 32–128 MB) y con la cuota de disco de `storage/app/public`. Hay que verificarlo en la Fase 0 y, si hace falta, subirlo por `.htaccess`/`php.ini` o subir el vídeo en trozos. TikTok con `FILE_UPLOAD` lee el archivo del disco (no necesita URL pública); LinkedIn sube el archivo en trozos de ~4 MB desde el disco.

---

## 3. LinkedIn — requisitos oficiales (2026)

Fuentes: Posts API oficial (Microsoft Learn, `learn.microsoft.com/en-us/linkedin/marketing/community-management/shares/posts-api`), guía 2026 verificada (outstand.so/blog/linkedin-posts-api, versión `202610`).

### 3.1 Qué hay que tener

| Requisito | Detalle |
|---|---|
| **App de LinkedIn** | Crear en el LinkedIn Developer Portal y asociarla a la página de HyperBit (un super admin de la página debe verificar la app) |
| **Producto: Community Management API** | Es lo que permite publicar en **páginas de empresa**. Requiere revisión manual de LinkedIn. Solo se concede a **organizaciones legales registradas con caso de uso comercial** — HyperBit IT es una LLC, encaja. Hacen falta email de empresa verificado y app verificada por la página |
| **Scopes** | `w_organization_social` (publicar en nombre de la organización), `r_organization_social` (leer posts), `rw_organization_admin` (listar organizaciones y analíticas). Opcional `openid`/`profile` para identidad |
| **Roles en la página** | Quien autoriza debe tener rol `ADMINISTRATOR`, `CONTENT_ADMIN` o `DIRECT_SPONSORED_CONTENT_POSTER` en la página de HyperBit (Jarvis es admin) |
| **OAuth** | Flujo authorization code de 3 patas (no hay tokens solo de app). Redirect URI HTTPS exacta |
| **Token** | `access_token` válido **60 días**. **Ojo:** LinkedIn solo da refresh tokens (365 días) a un grupo limitado de partners aprobados; lo normal es **re-autorizar cada 60 días** (si estás logueado y el token sigue válido, LinkedIn salta la pantalla de consentimiento). El plan debe incluir aviso en la app + recordatorio antes del día 60 |
| **Nivel inicial** | Tras aprobar, nivel **Development: 500 llamadas por app y 100 por miembro al día**. Pasar a Standard es una segunda revisión |
| **Versiones** | Cada llamada lleva `Linkedin-Version: YYYYMM` (ej. `202610`) y `X-Restli-Protocol-Version: 2.0.0`. Sin el header de versión, error. Cada versión vive ~1 año |

### 3.2 Flujo de publicación de vídeo (3 pasos + post)

1. `POST /rest/videos?action=initializeUpload` con `owner` (`urn:li:organization:{id}`), `fileSizeBytes`, `uploadCaptions: false`, `uploadThumbnail: false` → devuelve `video` URN, `uploadToken` y `uploadInstructions` (un `uploadUrl` por trozo de ~4 MB, con `firstByte`/`lastByte`).
2. `PUT` cada trozo a su `uploadUrl`, guardando el header `ETag` de cada parte.
3. `POST /rest/videos?action=finalizeUpload` con el URN del vídeo, el `uploadToken` y los `uploadedPartIds` (los ETags en orden).
4. Esperar a que `GET /rest/videos/{urn}` devuelva `status: AVAILABLE` y luego `POST /rest/posts` con `author: urn:li:organization:{id}` y `content.media.id: urn:li:video:{id}`. Respuesta `201` vacía; el ID del post viene en el header `x-restli-id` (`urn:li:share:` o `urn:li:ugcPost:`).

### 3.3 Límites y reglas del contenido

- **Vídeo:** MP4, **75 KB–500 MB** por la API (la subida manual acepta hasta 5 GB; la API dice 500 MB — usar 500 MB como techo seguro), **mínimo 3 s**, **hasta 10 min en página** (la API admite hasta 30 min, pero tratar más de 10 min como arriesgado). Los vídeos de Jarvis (~1 min, ~50 MB) cumplen de sobra.
- **Texto (`commentary`):** máximo **3.000 caracteres**. No es texto plano: es el "little text format" de LinkedIn — hay que **escapar** los caracteres reservados `| { } @ [ ] ( ) < > # \ * _ ~` (si no, un texto con "(beta)" o "C#" se rechaza o se corta) y los hashtags van como `{hashtag|\#|tag}`.
- **Un vídeo por post.** Multi-imagen: 2–20 imágenes (no es el caso de Jarvis).
- **Sin programación nativa:** `PUBLISHED` es el único `lifecycleState` aceptado al crear. La cola de HyperBit Social es lo que programa.
- **Sin documentos** por la API (2026). Polls/artículos no aplican aquí.
- **Analítica (opcional, fuera del MVP):** `GET /rest/organizationalEntityShareStatistics` (impresiones, clics, reacciones, comentarios) requiere `rw_organization_admin` + rol ADMINISTRATOR.

### 3.4 Lo que NO se puede

- Publicar en una página donde el usuario que autorizó no sea admin/content admin (el 403 más común; hay que re-chequear `organizationAcls` cuando falle).
- Publicar como perfil personal con `w_organization_social` (eso es `w_member_social`, producto "Share on LinkedIn", self-service, instantáneo — ver §6.3 como atajo parcial).
- Programar dentro de LinkedIn: la herramienta publica a la hora; si el cron o el token falla a esa hora, el post no sale (por eso existe `MAX_RETRIES = 3` en `Post`).
- Leer analíticas con solo `w_organization_social` (es de escritura; hace falta `rw_organization_admin`).

---

## 4. TikTok — requisitos oficiales (2026)

Fuentes: TikTok for Developers — Content Posting API (guías verificadas contra los docs oficiales en sep–nov 2026: postbase.so, abondy.com, skedcast.com; token management oficial en developers.tiktok.com/doc/oauth-user-access-token-management/).

### 4.1 Qué hay que tener

| Requisito | Detalle |
|---|---|
| **Cuenta de desarrollador + app** | Crear en TikTok for Developers. Añadir el producto **Content Posting API** y **Login Kit** (el OAuth) |
| **Scopes** | `user.info.basic` (identidad) + `video.publish` (Direct Post) o `video.upload` (Upload a bandeja). Pedir solo los necesarios |
| **OAuth (Login Kit)** | Authorize: `https://www.tiktok.com/v2/auth/authorize/` — **ojo: TikTok usa `client_key`, no `client_id`**. Token: `POST https://open.tiktokapis.com/v2/oauth/token/` |
| **Tokens** | `access_token` **24 horas**; `refresh_token` **365 días** (rota: el refresh puede devolver uno distinto — hay que guardar el nuevo). Renovación sin consentimiento del usuario, ideal con un job diario |
| **Revisión de la app** | Verificación de URL + revisión con **al menos un vídeo demo** mostrando el flujo completo de extremo a extremo. Hacen falta Privacy Policy y Terms (el sitio ya tiene `/privacy` y `/terms`, reutilizables). Cambios posteriores a la aprobación se re-revisan |
| **Auditoría (lo crítico)** | Para que los posts sean **públicos** hay que pasar la auditoría de TikTok. Sin ella: todos los posts salen **privados (`SELF_ONLY`)**, las cuentas deben estar en **privado** (si no, error `unaudited_client_can_only_post_to_private_accounts`) y máximo **5 usuarios** pueden postear en 24 h. Sin plazo publicado para la auditoría |

### 4.2 Dos modos de publicar (elegir)

| | Direct Post (`video.publish`) | Upload a bandeja (`video.upload`) |
|---|---|---|
| Qué pasa | El vídeo se publica directo en el perfil | Va como borrador a la bandeja del creador; él lo termina en la app de TikTok |
| Auditoría para posts públicos | **Sí, obligatoria** | No (lo publica el creador) |
| Sirve para programar solo | **Sí** | No (alguien tiene que abrir TikTok) |
| Límites | ~15 posts/día por creador; 6 peticiones/min por token | Máx 5 borradores pendientes en 24 h |
| Uso recomendado | El objetivo final | **Atajo mientras llega la auditoría** |

### 4.3 Flujo de Direct Post

1. `POST /v2/post/publish/creator_info/query/` → `creator_nickname`, `creator_avatar_url`, `privacy_level_options`, `comment_disabled`, `duet_disabled`, `stitch_disabled`, `max_video_post_duration_sec`. **Obligatorio antes de cada post**: define qué privacidad se puede elegir y la duración máxima de ese creador (algunos están limitados a 3, 5 o 10 min).
2. `POST /v2/post/publish/video/init/` con `post_info` (title, privacy_level, disable_comment/duet/stitch, brand_content_toggle, brand_organic_toggle, `is_aigc`) y `source_info`: `FILE_UPLOAD` (video_size, chunk_size, total_chunk_count) o `PULL_FROM_URL` (requiere dominio verificado). Devuelve `publish_id` y `upload_url` (válida 1 h).
3. `PUT` los trozos al `upload_url` con `Content-Range` (trozos de **5–64 MB**, el último hasta 128 MB; un vídeo de menos de 64 MB sube en un solo trozo). Los vídeos de Jarvis (~50 MB) suben en un trozo.
4. Poll `POST /v2/post/publish/status/fetch/` con `publish_id` hasta `PUBLISH_COMPLETE` (o fallo). El procesado tarda de segundos a minutos.

### 4.4 Límites y reglas del contenido

- **Vídeo:** MP4 (recomendado), WebM o MOV; H.264 (recomendado), H.265, VP8, VP9; **hasta 4 GB y 10 min**; 23–60 FPS. Caption (`title`) hasta **2.200 caracteres**.
- **Fotos (fuera del MVP):** solo `PULL_FROM_URL` (dominio verificado), WebP/JPEG, hasta 1080p y 20 MB cada una, hasta 35 fotos.
- **Etiqueta de IA:** el campo `is_aigc` marca el contenido como generado por IA. Los vídeos de Jarvis ya los etiqueta TikTok como IA; usar el flag es lo correcto.
- **La pantalla de publicación debe cumplir las Content Sharing Guidelines** (la auditoría la revisa con una grabación de pantalla):
  1. Mostrar quién publica: nickname (y avatar) del creador desde `creator_info`.
  2. Privacidad en desplegable **sin valor por defecto** (solo las opciones de `privacy_level_options`); el creador debe elegir.
  3. Toggles de comentarios/duet/stitch, todos **apagados por defecto**, y bloqueados si el creador los tiene apagados en TikTok.
  4. Interruptor de contenido comercial, apagado por defecto; si se activa, hay que marcar "Tu marca" o "Contenido de marca".
  5. Línea de consentimiento junto al botón: "By posting, you agree to TikTok's Music Usage Confirmation" (+ Branded Content Policy si aplica).
  6. Vista previa del vídeo y título editable.
  7. **Nada sale sin una acción explícita** (pulsar publicar o programar).
  8. Respetar `max_video_post_duration_sec` antes de subir.
  
  Esto significa que el formulario de crear post necesita **opciones por canal** para TikTok (privacidad, toggles) — hoy el formulario es igual para todos los canales. Es trabajo de UI, no solo de API.

### 4.5 Lo que NO se puede

- Publicar en público sin pasar la auditoría (todo sale privado y la cuenta debe ser privada).
- Programar sin Direct Post: Upload-to-inbox exige que una persona termine el post en TikTok.
- Superar 6 peticiones/min por token ni ~15 posts/día por creador.
- Usar `PULL_FROM_URL` con un dominio no verificado en la app de TikTok (por eso se recomienda `FILE_UPLOAD`, que no necesita verificación).

---

## 5. Qué tiene que hacer Jarvis (trámites) vs qué es código

### 5.1 Jarvis — LinkedIn (sus manos, ~2–3 h + esperas)

- [ ] Crear la app en el LinkedIn Developer Portal y asociarla a la página de HyperBit IT.
- [ ] Como super admin de la página, **verificar la app** en el portal.
- [ ] Añadir el producto **Community Management API** y enviar la solicitud de acceso: describir el caso de uso (programador propio para publicar vídeos en la página de su empresa), email de empresa verificado, y lo que pidan (descripción, capturas o vídeo demo de la herramienta).
- [ ] Copiar Client ID y Client Secret a `.env` (`LINKEDIN_CLIENT_ID`, `LINKEDIN_CLIENT_SECRET`) y registrar la redirect URI `https://social.hyperbitit.com/auth/linkedin/callback` en la pestaña Auth.
- [ ] Conectar su página en HyperBit Social (flujo OAuth) y autorizar `w_organization_social` (+ `r_organization_social`, `rw_organization_admin`).
- [ ] **Cada ~55 días:** re-autorizar LinkedIn (el token dura 60 días y normalmente no hay refresh token). La app le avisará con un banner.
- [ ] Esperar la revisión (sin plazo; planificar semanas). Si la rechazan, LinkedIn dice el motivo — se corrige y se reenvía.

### 5.2 Jarvis — TikTok (sus manos, ~2–3 h + esperas)

- [ ] Crear cuenta de desarrollador y app en TikTok for Developers; añadir **Content Posting API** + **Login Kit**.
- [ ] Activar el ajuste **Direct Post** en el producto Content Posting API.
- [ ] Configurar redirect URI `https://social.hyperbitit.com/auth/tiktok/callback`, Privacy Policy y Terms (ya existen en el sitio).
- [ ] Pedir los scopes `user.info.basic` + `video.publish` (y `video.upload` para el atajo de bandeja).
- [ ] Grabar el **vídeo demo** del flujo completo (conectar cuenta → crear post con vídeo → programar → publicar) y enviar la **auditoría** con la justificación de audiencia amplia (ver riesgo nº 1, §7).
- [ ] Conectar su cuenta @hyperbit_it0 en HyperBit Social (flujo OAuth) y elegir privacidad por defecto.
- [ ] Esperar la revisión de la app y la auditoría (sin plazo; planificar semanas).

### 5.3 Código (Cursor + Jarvis revisando)

Todo lo de la §6. Nada de esto lo puede hacer Jarvis a mano: es desarrollo en el repo, por PRs como las #5–#9.

---

## 6. Plan de implementación por fases

**Reglas para todas las fases:** un PR por fase (o por sub-tarea grande), tests con `Http::fake()` como los de `FacebookPublisherTest`, `php artisan test` + Pint en CI antes de mergear, y deploy a Hostinger con `migrate --force` tras cada fase con migración. Nada se prueba publicando en las cuentas reales sin el modo de pruebas de la §8.

### Fase 0 — Vídeo en HyperBit Social (prerrequisito de todo) · ~6–10 h

Sin esto no hay LinkedIn ni TikTok, y además deja preparada la futura subida de Reels a Instagram.

| # | Tarea | Archivos |
|---|---|---|
| 0.1 | Aceptar vídeo en la creación de posts: `mp4,mov` (+ `webm` para TikTok), límite de tamaño subido (p. ej. 500 MB o lo que permita el hosting), validación de duración con `ffprobe` si está disponible (rechazar <3 s y >10 min para LinkedIn) | `app/Http/Requests/StorePostRequest.php`, `UpdatePostRequest.php` |
| 0.2 | Guardar vídeo en disco `public` igual que las imágenes; `Post::hasMedia()` ya funciona; añadir `Post::hasVideo()` / `isVideo()` por `media_type` y vista previa de vídeo en `posts/create` y `posts/show` | `app/Models/Post.php`, `app/Http/Controllers/PostController.php`, `resources/views/posts/create.blade.php`, `resources/views/posts/show.blade.php` |
| 0.3 | Verificar límites de Hostinger (`upload_max_filesize`, `post_max_size`, cuota de disco) y ajustarlos (`.htaccess`/`php.ini` del hosting) o documentar el techo real. Si el techo es bajo, subir el vídeo en trozos desde el navegador (trabajo mayor — decidir en esta fase) | Docs + `.htaccess` del proyecto |
| 0.4 | Tests: subir mp4 válido, rechazar .avi/.exe, rechazar >límite | `tests/Feature/PostsTest.php` |

### Fase 1 — LinkedIn (página de empresa) · ~12–18 h de código + revisión

**Primero los trámites (§5.1): enviar la solicitud de Community Management el día 1 de la fase** — la revisión corre en paralelo al código.

| # | Tarea | Archivos |
|---|---|---|
| 1.1 | Configuración: `config/linkedin.php` (`client_id`, `client_secret`, `redirect_uri`, `api_version` p. ej. `202610`, base `https://api.linkedin.com`) + vars en `.env.example` (`LINKEDIN_CLIENT_ID`, `LINKEDIN_CLIENT_SECRET`, `LINKEDIN_REDIRECT_URI`, `LINKEDIN_API_VERSION`) | `config/linkedin.php`, `.env.example` |
| 1.2 | Cliente API: `app/Services/LinkedIn/LinkedInApiClient.php` (patrón `MetaGraphClient`: HTTP Laravel, timeout, retry, excepciones propias `LinkedInApiException`) con métodos: `getAuthorizationUrl()`, `exchangeCodeForToken()`, `getUserInfo()`, `listOrganizations()` (`/rest/organizationAcls`), `initializeVideoUpload()`, `uploadVideoPart()`, `finalizeVideoUpload()`, `getVideoStatus()`, `createPost()`, `getPost()` | `app/Services/LinkedIn/LinkedInApiClient.php`, `app/Services/LinkedIn/LinkedInApiException.php` |
| 1.3 | OAuth conectar-cuenta: `Auth/LinkedInController@redirect/callback` (state anti-CSRF, intercambio de código, guardar `access_token` + `token_expires_at` + `platform_id` = org id, `platform_name` = nombre de la página). Página para elegir organización si administra varias | `app/Http/Controllers/Auth/LinkedInController.php`, `routes/web.php`, vista de selección de organización |
| 1.4 | Modelo: constante `SocialAccount::PLATFORM_LINKEDIN_ORGANIZATION = 'linkedin_organization'` + `isLinkedIn()`, icono/etiqueta en `getPlatformIcon()`/`getPlatformLabel()` | `app/Models/SocialAccount.php` |
| 1.5 | Publisher: `app/Services/Publishers/LinkedInPublisher.php` (implementa `PublisherInterface`): texto → `POST /rest/posts` directo; vídeo → initialize → PUT trozos (~4 MB) → finalize → poll hasta `AVAILABLE` (reutilizar `PublishResult::pending()` + `container_id` para el URN) → `POST /rest/posts` con el URN; escapar `commentary` (caracteres reservados + hashtags); `markAsUsed()`/`markError()` como Facebook | `app/Services/Publishers/LinkedInPublisher.php` |
| 1.6 | Registrar en `PublisherManager` (constructor + `getSupportedPlatforms()`) y en `MetaServiceProvider` (o un `LinkedInServiceProvider` nuevo, más limpio) | `app/Services/Publishers/PublisherManager.php`, `app/Providers/*` |
| 1.7 | Migración tokens: `refresh_token` (texto, encriptado vía cast como `access_token`) + `refresh_token_expires_at` en `social_accounts` + comando `tokens:refresh` (diario, vía `routes/console.php`) que renueve TikTok y LinkedIn si hay refresh token; banner en la app cuando el token de LinkedIn esté a <7 días de caducar | Migración nueva, `app/Console/Commands/RefreshTokens.php`, `resources/views/accounts/index.blade.php` |
| 1.8 | Comando de pendientes: `posts:check-pending` cada 2–5 min (ver §2.4.3): re-pregunta el estado de los canales en `publishing` con `container_id` y marca publicado/fallido. Imprescindible para el vídeo de LinkedIn (el procesado tarda minutos) | `app/Console/Commands/CheckPendingPosts.php`, `routes/console.php` |
| 1.9 | Tests con `Http::fake()`: texto publicado (201 + header `x-restli-id`), subida de vídeo en 3 pasos, vídeo aún procesando → `pending`, error 403 → mensaje de reconexión, escape de `commentary` | `tests/Feature/LinkedInPublisherTest.php`, `tests/Feature/LinkedInAuthTest.php` |
| 1.10 | Docs: actualizar README (LinkedIn en features + setup) y `docs/` con la guía de la app de LinkedIn | `README.md`, `docs/LINKEDIN_SETUP.md` |

### Fase 2 — TikTok · ~12–18 h de código + auditoría

**Primero los trámites (§5.2): crear la app y pedir los scopes el día 1.** El código de Upload-to-inbox puede probarse sin auditoría; Direct Post necesita la auditoría para publicar en público.

| # | Tarea | Archivos |
|---|---|---|
| 2.1 | Configuración: `config/tiktok.php` (`client_key`, `client_secret`, `redirect_uri`, base `https://open.tiktokapis.com`) + vars en `.env.example` (`TIKTOK_CLIENT_KEY`, `TIKTOK_CLIENT_SECRET`, `TIKTOK_REDIRECT_URI`) | `config/tiktok.php`, `.env.example` |
| 2.2 | Cliente API: `app/Services/TikTok/TikTokApiClient.php` con: `getAuthorizationUrl()` (con `client_key`), `exchangeCodeForToken()`, `refreshAccessToken()`, `getUserInfo()`, `getCreatorInfo()` (`/v2/post/publish/creator_info/query/`), `initVideoPublish()` (FILE_UPLOAD y PULL_FROM_URL), `uploadVideoChunk()`, `getPublishStatus()`, `initInboxVideoUpload()` | `app/Services/TikTok/TikTokApiClient.php`, `app/Services/TikTok/TikTokApiException.php` |
| 2.3 | OAuth conectar-cuenta: `Auth/TikTokController@redirect/callback` (guardar `open_id` como `platform_id`, `access_token` + `refresh_token` + expiraciones, `platform_username` = nickname) | `app/Http/Controllers/Auth/TikTokController.php`, `routes/web.php` |
| 2.4 | Modelo: constante `SocialAccount::PLATFORM_TIKTOK = 'tiktok'` + `isTikTok()`, icono/etiqueta | `app/Models/SocialAccount.php` |
| 2.5 | Publisher: `app/Services/Publishers/TikTokPublisher.php`: Direct Post por defecto (init → PUT trozos 5–64 MB → poll `status/fetch` hasta `PUBLISH_COMPLETE`, con `PublishResult::pending()` + `container_id` = `publish_id`); modo Upload-to-inbox como alternativa por post (o por cuenta) mientras no haya auditoría; `creator_info` antes de cada post (privacidad y duración máx.); `is_aigc: true` para sus vídeos | `app/Services/Publishers/TikTokPublisher.php` |
| 2.6 | UI por canal: el formulario de crear post necesita campos TikTok — desplegable de privacidad (sin valor por defecto, solo las opciones del creador), toggles de comentarios/duet/stitch (apagados por defecto), interruptor de contenido comercial, línea de consentimiento, vista previa del vídeo y título editable. Guardar esas opciones por `PostChannel` (p. ej. columna `options` JSON en `post_channels`, o campos en `posts`) | `resources/views/posts/create.blade.php`, migración `post_channels.options` (JSON nullable), `app/Models/PostChannel.php` |
| 2.7 | Registrar en `PublisherManager` y proveedor de servicios; el `tokens:refresh` de la Fase 1 ya cubre TikTok (24 h → refresco diario obligatorio) | `app/Services/Publishers/PublisherManager.php`, `app/Providers/*` |
| 2.8 | Tests con `Http::fake()`: init + subida en 1 trozo + poll hasta `PUBLISH_COMPLETE`, vídeo demasiado largo según `creator_info` → error claro, token expirado → refresh automático y reintento, modo inbox | `tests/Feature/TikTokPublisherTest.php`, `tests/Feature/TikTokAuthTest.php` |
| 2.9 | Auditoría: grabar el vídeo demo con el flujo real (conectar → crear → programar → publicar en la cuenta de pruebas) y enviarla. Sin la auditoría, Direct Post solo publica en privado — no lanzar como "listo" hasta pasarla | Trámite de Jarvis (§5.2) |
| 2.10 | Docs: README + `docs/TIKTOK_SETUP.md` | `README.md`, `docs/TIKTOK_SETUP.md` |

### Fase 3 — Cierre y endurecimiento · ~6–8 h

| # | Tarea |
|---|---|
| 3.1 | Analítica básica (opcional): LinkedIn `organizationalEntityShareStatistics` (requiere `rw_organization_admin`) y TikTok `GET /v2/video/query/` — mostrar impresiones/reproducciones en `posts/show`. Fuera del MVP; hacerlo solo si sobra tiempo |
| 3.2 | Reintentos inteligentes: si un canal falla por token expirado, refrescar y reintentar una vez antes de marcar `failed` (hoy `MAX_RETRIES = 3` reintenta el post entero en la siguiente ejecución del cron) |
| 3.3 | Avisos: banner "tu LinkedIn caduca en X días, reconecta" y "tu TikTok necesita re-autorización" (cuando el refresh token esté a <30 días de caducar) |
| 3.4 | Limpieza: borrar el vídeo del disco tras publicar (o a los N días) para no llenar el hosting; documentar la política |
| 3.5 | README y roadmap actualizados (quitar LinkedIn de "Roadmap", añadir "Instagram Reels" como siguiente paso natural — la Fase 0 ya lo deja preparado) |

### Esfuerzo total

| Fase | Código (con Cursor) | Trámites de Jarvis | Espera externa |
|---|---|---|---|
| Fase 0 — Vídeo | 6–10 h | 0,5 h (verificar límites del hosting) | — |
| Fase 1 — LinkedIn | 12–18 h | 2–3 h | Revisión Community Management: semanas, sin plazo |
| Fase 2 — TikTok | 12–18 h | 2–3 h (incl. grabar demo) | Revisión + auditoría: semanas, sin plazo |
| Fase 3 — Cierre | 6–8 h | — | — |
| **Total** | **~35–50 h** | **~5–7 h** | **4–8 semanas de calendario realista** |

---

## 7. Riesgos y bloqueadores (ordenados por peligro)

1. **TikTok puede rechazar la auditoría por ser una herramienta "privada".** Las directrices de Direct Post exigen que el cliente esté pensado para una audiencia amplia, no para uso interno; el ejemplo que TikTok pone de lo que falla es justo "una herramienta que sube a las cuentas que tú o tu equipo gestionáis". **Mitigación:** HyperBit Social es open-source y multi-usuario por diseño (cualquiera puede instalarlo y conectar sus cuentas) — presentar la app así en la auditoría: scheduler abierto para creadores, no herramienta interna. Si la rechazan, el plan B es Upload-to-inbox (funciona sin auditoría, con el paso manual en TikTok) mientras se apela o se re-enfoca la solicitud. **Este es el mayor riesgo del proyecto.**
2. **LinkedIn Community Management sin plazo.** No hay timeline publicado; son semanas y dos fases (Development y luego Standard). **Mitigación:** enviar la solicitud el día 1 de la Fase 1 y construir el código mientras tanto. Mientras no esté aprobada, la página se sigue publicando a mano en linkedin.com (y el vídeo personal sí se puede automatizar con "Share on LinkedIn", self-service — ver §6.3).
3. **Token de LinkedIn de 60 días sin refresh.** Si se le olvida re-autorizar, las publicaciones programadas empiezan a fallar en silencio. **Mitigación:** banner en la app + recordatorio (el briefing matutino puede avisar cuando falten <7 días).
4. **Token de TikTok de 24 h.** Sin el job de refresco diario, ninguna publicación programada de TikTok funcionaría al día siguiente. **Mitigación:** `tokens:refresh` diario (Fase 1.7) + refresco bajo demanda antes de publicar.
5. **Límites de Hostinger.** Vídeos de ~50 MB: `upload_max_filesize`, `post_max_size` y cuota de disco. **Mitigación:** verificar en la Fase 0; si el techo es bajo, subir el vídeo en trozos o comprimir antes de subir.
6. **Vídeo de LinkedIn: procesado asíncrono.** Un vídeo de 50 MB puede tardar minutos en estar `AVAILABLE`. Sin el comando `posts:check-pending` (Fase 1.8), el post quedaría atascado en `publishing`. Es el mismo hueco que hoy tiene Instagram.
7. **La UI de TikTok la revisa un humano.** La auditoría mira la pantalla de publicación con una grabación: si falta la privacidad sin defecto, los toggles apagados o la línea de consentimiento, no pasa. **Mitigación:** implementar la §4.4 al pie de la letra y grabar la demo con esa pantalla.
8. **Cambios en las APIs.** LinkedIn versiona por mes (`Linkedin-Version: YYYYMM`, cada versión vive ~1 año) y TikTok re-revisa la app ante cambios. **Mitigación:** la versión de LinkedIn en config (un cambio de línea), y no tocar la app de TikTok tras la aprobación sin re-revisión.

## 8. Cómo probar sin spamear las páginas reales

- **LinkedIn:** la app en Development solo puede publicar en las organizaciones del entorno de pruebas; para el primer post real, usar un vídeo de prueba corto en la página de HyperBit y borrarlo después, o mejor: crear una **página de pruebas** de LinkedIn (una Page solo para esto) y conectarla primero. Los tests con `Http::fake()` cubren el flujo sin tocar la red.
- **TikTok:** antes de la auditoría todo sale privado y la cuenta debe estar en privado — eso ya es un entorno de pruebas natural. Usar una cuenta de TikTok secundaria/privada para el desarrollo y la demo de la auditoría; la cuenta real @hyperbit_it0 solo se conecta cuando Direct Post ya publica en público.
- **General:** `publish-now` en un post de prueba con un vídeo de 5 s, verificar `platform_post_id` + `permalink` en `posts/show`, y borrar el post de prueba en la red. El comando `posts:check-pending` se prueba forzando un vídeo largo y viendo que el estado pasa de `publishing` a `published` solo.
- **Nunca** probar con los vídeos reales de la semana ni con las cuentas de clientes (no hay clientes conectados hoy, pero la regla es la misma).

## 9. Impacto en el código actual de Facebook/Instagram

**Casi nulo, y a propósito.** Todo es aditivo:

- **No se tocan** `FacebookPagePublisher`, `InstagramPublisher`, `MetaGraphClient`, `PublishDuePosts` ni `FacebookController`.
- **Cambios compartidos (pequeños):** `StorePostRequest` (aceptar vídeo), `PostController::store()` (guardar vídeo), `SocialAccount` (2 constantes + migración de `refresh_token`), `PublisherManager` (registrar 2 publishers), `posts/create` (vista previa de vídeo + opciones TikTok), `routes/console.php` (2 comandos nuevos), `.env.example` + 2 configs nuevas.
- **Mejora gratis para Instagram:** la Fase 0 (subida de vídeo) + el comando `posts:check-pending` dejan preparado el futuro soporte de **Reels** (la API de IG acepta `video_url` con `media_type=REELS` en el mismo flujo de contenedor que ya existe). No entra en este plan, pero es el siguiente paso natural.
- **Threads** sigue como stub; este plan no lo toca.

## 10. MVP recomendado (una red primero: LinkedIn)

**Alcance mínimo que ya le sirve a Jarvis:** Fase 0 + Fase 1 hasta la 1.9, solo vídeo + texto en la **página de empresa**, con programación por el cron existente y `posts:check-pending`. Sin analítica, sin perfil personal, sin TikTok. Con eso el vídeo de HyperBit Social (y todos los de la semana) se programa desde su herramienta en la red donde están sus compradores. TikTok entra después con el atajo de Upload-to-inbox primero y Direct Post cuando pase la auditoría.

**Orden de trabajo sugerido:**

1. **Hoy:** Jarvis envía la solicitud de Community Management de LinkedIn (no depende del código).
2. **Semana 1:** Fase 0 (vídeo) + Fase 1.1–1.6 (cliente, OAuth, publisher) con tests.
3. **Semana 2:** Fase 1.7–1.10 (tokens, pendientes, docs) + crear la app de TikTok y pedir scopes.
4. **Cuando llegue la aprobación de LinkedIn:** conectar la página, primer vídeo de prueba, a producción.
5. **Después:** Fase 2 (TikTok) + auditoría. **Mientras tanto, el vídeo del sábado 28 se sigue programando a mano (navegador) como hasta ahora — este plan no cambia nada de esta semana.**

---

## 11. Fuentes

- LinkedIn Posts API (oficial, Microsoft Learn): https://learn.microsoft.com/en-us/linkedin/marketing/community-management/shares/posts-api
- LinkedIn Posts API — guía 2026 (versión `202610`, Community Management, vídeos): https://www.outstand.so/blog/linkedin-posts-api
- LinkedIn — automatizar página de empresa (Posts API, vídeos, límites): https://postproxy.dev/blog/linkedin-api-automate-company-page-publishing/
- TikTok Content Posting API — cómo postear y pasar la auditoría (verificado contra docs oficiales 26 sep 2026): https://www.postbase.so/blog/tiktok-content-posting-api
- TikTok Content Posting API — guía de publicación (verificado nov 2026): https://abondy.com/blog/tiktok-api-post-video
- TikTok Content Posting API — auditoría, reglas y límites: https://skedcast.com/resources/guides/tiktok-content-posting-api
- TikTok — User Access Token Management (oficial: access 24 h, refresh 365 días): https://developers.tiktok.com/doc/oauth-user-access-token-management/
- LinkedIn — límites de vídeo por API (MP4 75 KB–500 MB, 3 s–10 min en página): https://docs.postzen.dev/platforms/linkedin
- Repo HyperBit Social (leído completo, commit `003f1cf`): https://github.com/kaiser5474/hyperbit-social
