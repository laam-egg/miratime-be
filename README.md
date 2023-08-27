# BACKEND of Miratime - Timekeeper System for Miraculous Company

FRONTEND: <https://github.com/laam-egg/miratime-fe>

## Get Started
0. Clone the project to local machine.
1. Copy `.env.example` into a new file called `.env`.
2. Update the following variables in `.env` if necessary:
    ```
    APP_ENV=...<local, testing or production>
    APP_DEBUG=...<true or false>
    APP_URL=...<The application host (Backend) URL>
    APP_FRONTEND_URL=...<URL to the Frontend of the application>

    DB_CONNECTION=...
    DB_HOST=...
    DB_PORT=...
    DB_DATABASE=...
    DB_USERNAME=...
    DB_PASSWORD=...
    ```

    In production, the application's frontend and backend are currently hosted on these domains:
    ```
    APP_URL=https://miratime-api.vutunglam.id.vn
    APP_FRONTEND_URL=https://miratime.vutunglam.id.vn
    ```

3. Run:
    ```shell
    php artisan key:generate
    php artisan app:jwt
    php artisan migrate
    ```

## Frontend/API Client - General Notes
1. JSON format is used for communication between backend and frontend. Therefore, always set the following request headers:
    ```
    Accept: application/json
    Content-Type: application/json
    ```

2. Make sure cookies are received from and sent to server properly, since the backend uses cookies to implement "remember login" functionality (using refresh tokens). The backend may take advantage of cookies for other purposes as well.

## Authentication

### General Idea
The client sends credentials (email and password) to the API in order to log in. Upon success, the client will be provided an **access token** in the response body, along with a **refresh token** hidden in an httpOnly cookie (which can only be accessed by server-side code ; for reasons, see [Security Considerations](#security-considerations)).

In subsequent requests, the client is required to embed the access token into the Authorization header as a Bearer token:
```
Authorization: Bearer <access_token>
```

This way, the client can prove its identity to access protected routes. However, **an access token expires in 30 minutes since it is issued, or when the user explicitly logs out from the device, or when a token refresh takes place, whichever happens first**. When it is expired, requests comprising the token will be rejected with a `401` HTTP response. To obtain a new access token, the client has to visit the API endpoint `/api/auth/refresh` (provided that the refresh token still resides secretly in the user agent as the mentioned httpOnly cookie). The expiration conditions are set such that in case the access token is compromised, negative security impacts are minimalized.

An API endpoint to log the user out is also provided. When requesting this endpoint, both access and refresh tokens are invalidated, and the refresh token is also removed from the user agent.

### Security Considerations
**Best practice is to save the access token in fine-protected browser memory**, i.e. a JavaScript variable inside a Javascript closure. The next time user visits the website, just obtain a new access token as instructed above (reaching the endpoint `api/auth/refresh`) ; and if that fails, prompt the user to log in again.

Please note that we don't have to provide a live access token when requesting `/api/auth/refresh`, so the aforementioned practice is easy to implement.

However, an attacker might use XSS and XSRF techniques to secretly access `/api/auth/refresh`, get a valid access token and act as if it were the legitimate user on its own. To prevent this, both the backend and the frontend need to take several measures:

 - The backend always sends appropriate Cross Origin Resource Sharing (CORS) headers such that in a browser context, a third-party script can neither access the refresh token cookie, nor embed the cookie into its own requests. (That is the point of setting refresh token as an httpOnly cookie: to keep it away from malicious JavaScript in browser).

 - The frontend/API client:

    + The frontend or a browser-based API client needs to make sure certain headers be set. See [Required Request Headers](#required-request-headers). Also set `withCredentials=true` (please google for how to set this using `XHR`, `fetch` or libraries like `axios`). The frontend's server should also implement Content Security Policies (CSPs) (possibly with nonces) [to further mitigate XSS attacks](https://developer.mozilla.org/en-US/docs/Web/HTTP/CSP#using_csp).

    + In case of non-browser context, an API client should set required headers properly, and manually embed previously-set cookies in request headers, as well as take entire responsibility of safeguarding access and refresh tokens.

### Required Request Headers
To sum up, following are the request headers that both the frontend and API clients need to make available. If there's just a value, that concrete value is mandatory. Otherwise, an explanation is provided instead.

```
User-Agent: <This header is automatically set by the browser or tools like Postman, Thunder Client and curl. You may leave it intact or customize it.>

Content-Type: application/json

Accept: application/json

X-MIRATIME-XSRF-PROTECTION: 1
<See below for explanation>

Authorization: Bearer <access token>
<in case the user has not logged in, do not set this header>
```

The *custom header* `X-MIRATIME-XSRF-PROTECTION` needs to be set in a browser context to prevent JavaScript from an alien origin (i.e. not backend or frontend URL) from forging authenticated requests to API endpoints, since [JavaScript code in browser cannot make requests to API endpoints of different origin containing a custom header](https://cheatsheetseries.owasp.org/cheatsheets/Cross-Site_Request_Forgery_Prevention_Cheat_Sheet.html#custom-request-headers). In case of non-browser context (Postman, Thunder Client, curl etc.), this header must also be specified. If this header is not present, the server will reject the request with a `403` response.

### Authentication Workflow
0. Prompt the user to fill in `email` and `password` credentials.
1. Initiate a `POST` request to `/api/auth/login` providing those credentials. If received a `200` response, user has logged in successfully => save the access token and move on. Otherwise, repeat step 0.
2. Embed the access token in request headers to authenticate for protected routes, as mentioned in the previous sections. Always check HTTP status code. As long as it is other than `401`, the access token is still valid. When HTTP status code is `401`, proceed to step 3.
3. Make a `POST` request to `/api/auth/refresh` to obtain new access token.
     - If received a `200` response (which means the user is already authenticated), use the new access token (and delete the old one, if any), and continue consuming other API endpoints like step 2.
     - If received a `401` response, the refresh token is no longer valid => prompt the user to log in again (step 0).

In fact, frontend and API clients should always start at step 3 for the "remember login" functionality.
