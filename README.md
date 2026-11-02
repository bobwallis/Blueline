# Blueline

Blueline is a web application for searching, exploring and visualising bell-ringing methods and related data.

Live site: [rsw.me.uk/blueline](https://rsw.me.uk/blueline/)

## Project goals

- **Primary mission:** Provide a fast, accessible and reliable web application for bellringers to look up, visualise and learn methods and related data.
- **Audience:** Bellringers of all skill levels, using desktop or mobile devices. Offline and low-connectivity use is common at tower practices and events.
- **Key experience:** Fast lookup, clear method diagrams and minimal visual noise.
- **Maintainability:** Prefer long-term-support software where practical, and avoid unnecessary third-party runtime frameworks.

## Features

- Search and browse a library of bell-ringing methods.
- Explore method details, diagrams, collections and performance records.
- Parse and convert place notation, and generate touches in the browser.
- Use the application offline after visiting pages while connected.
- Access read-only JSON and CSV endpoints.

## Quick start

The recommended development environment is the repository's VS Code Dev Container. From the repository root:

```sh
./bin/provision
./bin/fetchAndImportData
```

Open `http://localhost:8000/` after provisioning. See the [development guide](docs/development-deployment.md) for requirements, ports, tests and maintenance commands.

<!-- Add a project screenshot here when one is available. -->

## Contributing

Start with the [documentation index](docs/README.md), then read the [development guide](docs/development-deployment.md) and [repository instructions](AGENTS.md). UI work should follow the [design language](docs/design-language.md). Changes to behavior should include targeted tests and update the relevant canonical documentation.

## Licence

The repository's [LICENSE](LICENSE) is the GNU General Public License, version 3. Data provenance and attribution are documented in [Data and copyright](docs/data-and-copyright.md).

## Documentation

- [Documentation index](docs/README.md)
- [API reference](docs/api.md)
- [Production deployment](docs/production-deployment.md)
