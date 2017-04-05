Request rate limiter interface
-------------------------------------

Limits the number of requests per time.
 * There are two parameters: `period` and `rate`.
 * A request is a call of the `inc()` method. An `inc()` method begins to return false if number of requests per `period` is higher than a `rate`
