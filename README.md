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
1. JSON format is used for communication between backend and frontend. Also, the user agent plays an important role in authentication. Therefore, always set the following request headers:
    ```
    User-Agent: <this header is automatically set by the browser or API testing tools like Postman and Thunder Client ; but not curl, for example.>

    Accept: application/json
    Content-Type: application/json
    ```

2. Make sure cookies are received from and sent to server properly, since the backend uses cookies to implement "remember login" functionality (using refresh tokens). The backend may take advantage of cookies for other purposes as well.

## Authentication
### General Idea
The client sends credentials (email and password) to the API in order to log in. Upon success, the client will be provided an **access token** in the response body, along with a **refresh token** hidden in an httpOnly cookie (which can only be accessed by server-side code, preventing XSS).

In subsequent requests, the client is required to embed the access token into the Authorization header as a Bearer token:
    ```
    User-Agent: ...
    Content-Type: application/json
    Accept: application/json
    Authorization: Bearer <access_token>
    ```

This way, the client can prove its identity to access protected routes. However, **an access token expires in 30 minutes since it is issued**. When it is expired, request comprising the token will be rejected with a `401` HTTP response. To obtain a new access token, the client has to visit an API endpoint like `/api/auth/refresh` (provided that the refresh token still resides secretly in the user agent). The expiration period is set such that in case the access token is compromised, negative security impacts are minimalized.

An API endpoint to log the user out is also provided. When requesting this endpoint, both access and refresh tokens are invalidated, and the refresh token is also removed from the user agent.

### Authentication Workflow
TODO: Rewrite this with more details.

1. Initiate a `POST` request to `/api/auth/login` providing `email` and `password` credentials. If received a `200` response, user has logged in successfully => save the access token and move on.
2. Embed the access token in request headers to authenticate for protected routes, as mentioned in the previous section. Always check HTTP status code. As long as it is other than `401`, the access token is still valid.
3. When HTTP status code is `401`, make `POST` request to `/api/auth/refresh` to obtain new access token. If received a `200` response, replace the old access token with the new one, and continue consuming other API endpoints like step 2. If still received a `401` response, the refresh token is no longer valid => prompt the user to log in again.

Best practice is to save the access token in browser memory, i.e. a fine-protected JavaScript variable. The next time user visits the website, just obtain a new access token as instructed in step 3 ; and if that fails, prompt the user to log in again (back to step 1).

Please note that we don't have to provide a live access token when requesting `/api/auth/refresh`, so the aforementioned practice is easy to implement.
