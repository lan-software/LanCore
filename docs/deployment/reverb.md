# Reverb Deployment

LanCore uses [Laravel Reverb](https://reverb.laravel.com/) as the real-time broadcast transport for the Chat CSCI and (eventually) any presence push.

## Local development (Sail)

Reverb runs as a dedicated `reverb` container in `compose.yaml`, attached to both the `sail` bridge network (so the LanCore app can talk to it) and the `lanparty` external network (so satellite apps can reach it during development).

- Container: `lancore-reverb`
- Default host (from inside the network): `lancore-reverb:8080`
- Default port (host machine): `8080` (override via `REVERB_SERVER_PORT`)
- Healthcheck: `GET /health`

After pulling new changes that include the Reverb service for the first time:

```
vendor/bin/sail up -d
```

To verify Reverb is up:

```
vendor/bin/sail exec reverb curl -s http://localhost:8080/health
```

## Required environment variables

| Variable | Purpose | Dev default |
|----------|---------|-------------|
| `BROADCAST_CONNECTION` | Selects the broadcaster | `reverb` |
| `REVERB_APP_ID` | Pusher protocol app id | `lancore-dev` |
| `REVERB_APP_KEY` | Pusher protocol app key (public) | `lancore-dev-key` |
| `REVERB_APP_SECRET` | Pusher protocol app secret | `lancore-dev-secret` |
| `REVERB_HOST` | Host the **server** is reachable at | `lancore-reverb` |
| `REVERB_PORT` | Port the **server** is reachable at | `8080` |
| `REVERB_SCHEME` | `http` or `https` | `http` |
| `REVERB_SERVER_HOST` | Address the Reverb process binds to | `0.0.0.0` |
| `REVERB_SERVER_PORT` | Port the Reverb process binds to | `8080` |

The frontend reads `VITE_REVERB_APP_KEY`, `VITE_REVERB_HOST`, `VITE_REVERB_PORT`, and `VITE_REVERB_SCHEME` from `import.meta.env` at build time. These are interpolated from the corresponding `REVERB_*` values via `${VAR}` syntax in `.env`. Generate fresh, strong `REVERB_APP_*` values for staging and production.

## Production guidance

Run Reverb as a long-lived process under a supervisor (systemd unit, Octane-style supervisor, or a dedicated container). It is not request-scoped — clients keep the WebSocket connection open. Typical production sizing:

- One Reverb process per host is fine until ~10k concurrent connections; horizontal scaling requires a Redis-backed pub/sub channel layer (`config/reverb.php` → `apps.*.scaling`).
- Expose Reverb behind TLS (`REVERB_SCHEME=https`). Terminate TLS at the reverse proxy and forward the upgrade headers.
- Set `REVERB_HOST` to the public hostname clients will use, **not** the internal container hostname.

## Troubleshooting

- *"Failed to create broadcaster for connection reverb: Pusher::__construct(): Argument #1 ($auth_key) must be of type string, null given"* — the `REVERB_APP_KEY` env var is empty. Make sure `.env` has all `REVERB_*` keys populated.
- Browser console says "WebSocket connection failed" — confirm the **frontend's** `VITE_REVERB_HOST`/`VITE_REVERB_PORT` resolve from the user's machine (not from inside the container). In dev, that usually means `localhost:8080` rather than `lancore-reverb:8080`.

## Related documents

- SDD §3.2 (design decision: realtime transport = Reverb)
- IDD §3.14 (Chat broadcast payload contract)
- SRS §3.2.EE (Chat CSCI)
