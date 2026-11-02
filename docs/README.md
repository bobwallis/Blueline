# Documentation index

Use this index to find the maintained documentation and its canonical home.

## For contributors

- [Development and installation](development-deployment.md) — local setup, provisioning, testing and dependency updates.
- [Architecture and workflows](architecture-and-workflows.md) — application structure, offline behavior, worker safety and shared conventions.
- [Design language](design-language.md) — visual principles and shared UI tokens.
- [Touch generation](touch-generator.md) — search behavior, filters and ringing rules.
- [Repository instructions](../AGENTS.md) — coding expectations and exact commands for contributors and coding agents.

## For maintainers

- [Production deployment](production-deployment.md) — production setup, services, updates and operational behavior.
- [Data and copyright](data-and-copyright.md) — data sources, derived fields, database exports and attribution.

## For API consumers

- [API reference](api.md) — endpoints, parameters, response fields, CSV columns, status codes and caching.

## Documentation conventions

- Use sentence-case headings, British English, concise paragraphs and relative links within the repository.
- Add purpose, audience and verified prerequisites near the start of guides; include useful related-document links at the end.
- Keep each fact in its canonical guide. Update references when moving or renaming content.
- Copy-pasteable commands should be verified against repository scripts and their help output.
- Mark unresolved behavior with `TODO(owner)` instead of guessing.

## Canonical homes

| Topic | Canonical document |
| --- | --- |
| Local development, shared test database and test commands | [Development and installation](development-deployment.md) |
| Common provisioning and method-data import | [Development and installation](development-deployment.md) |
| Production services, tunnel and asset build | [Production deployment](production-deployment.md) |
| Architecture, worker safety, naming and PNG rendering | [Architecture and workflows](architecture-and-workflows.md) |
| Offline and service-worker behavior | [Architecture and workflows](architecture-and-workflows.md) |
| Touch search and ringing rules | [Touch generation](touch-generator.md) |
| Data provenance and derived data | [Data and copyright](data-and-copyright.md) |
| HTTP API and export schemas | [API reference](api.md) |
| Visual principles and shared UI tokens | [Design language](design-language.md) |
