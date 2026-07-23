# Contributing

Contributions are welcome! Please feel free to submit a pull request.

Security vulnerabilities must not be reported in public issues. Follow the
[Security Policy](SECURITY.md) instead.

## Development

To get started, you will need PHP 8.3+ with `mbstring`, Composer, Node.js, and npm.

1.  Fork the repository and clone it to your local machine.
2.  Install the dependencies:

    ```bash
    composer install
    npm install
    ```

3.  Run the tests:

    ```bash
    composer test
    ```

## Pull Requests

- Please ensure that `composer check` passes before submitting a pull request (runs platform check, format, lint, Prettier, tests, and PHPStan).
- Please follow the existing code style: Mago for PHP (`composer lint` / `composer format`); Prettier for YAML, JSON, Markdown (`npm run format`). A pre-commit hook runs Prettier check, Mago format, and Mago lint on staged files; run `npm run format` and `composer format` if checks fail.
- Please add a clear description of the changes you have made.
