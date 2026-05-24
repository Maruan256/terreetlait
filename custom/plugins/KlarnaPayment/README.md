# KlarnaPayment

## Frontend testing with Cypress

- Tests are located in tests/cypress
- The default URL for testing is `https://klarna.local`. Adjust the URL to your environment in `cypress.json`. 
- Copy `cypress.json.dist` to `cypress.json`
- Run the tests by executing `./node_modules/.bin/cypress open`. The command for pushing the test to cypress.io is configured in `package.json`
- Videos and screenshots will be placed in `/tests/cypress/screenshots` and `/tests/cypress/videos`. If running the tests headless with the command cypress:run in `package.json` the videos and screenshots will be uploaded to cypress.io.

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
