Request rate limiter interface
-------------------------------------

Limits the number of requests per time.
 * There are two parameters: `period` and `rate`.
 * A request is a call of the `inc()` method. An `inc()` method begins to return false if number of requests per `period` is higher than a `rate`

## Test notes
`test.sh` runs the PHPUnit tests.

`test-coverage.sh` generates the PHPUnit coverage analysis to the temp folder.

If the `php -v` command does not show the `with Xdebug` line, note that for coverage testing you might need to manually edit the php.ini used by your PHP CLI in order to enable the
```ini
; XDEBUG Extension
```
