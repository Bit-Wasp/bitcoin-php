
## Contributing

 Contributions are most welcome, and the more eyes on the code the better.

 Install phing on your development machine - it's used for tests, checking/fixing code style,
 and anything else you care to use it for!

### Check list
  * Write tests - pull requests should come with full coverage
  * Check the code style

### To get started:
  * Fork this library
  * Check out the code:
    - `git clone git@github.com:yourfork/bitcoin-php && cd bitcoin-php`
  * Start your own branch:
    - `git checkout -b your-feature-branch`
  * Check your work:
    - Codestyle check: `phing phpcs`
    - Codestyle fixer: `phing phpcbf`
    - Run tests: `phing phpunit`
    - Running `phing` will default to using `phpcbf` and `phpunit`, so running it before a commit is perfect.
  * Check code coverage: build/docs/code-coverage/index.html
  * Commit your work: `git commit ... `
  * Push your work:
    - `git push origin your-feature-branch`
  * And open a pull request!

 There will always be some iteration over new features - mainly this is to ensure classes
 don't run afoul of scope creep, and that the library remains precise and powerful.

 Please GPG sign your commits if possible: `git commit -S ...`

