<?php

namespace Technical\Oidc\Concerns;

use Laravel\Passport\Passport;
use League\OAuth2\Server\ResponseTypes\RedirectResponse;
use League\OAuth2\Server\ResponseTypes\ResponseTypeInterface;
use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Technical\Oidc\CurrentSsoSession;
use Technical\Oidc\Models\SsoSession;

trait CarriesTheSsoSession
{
    /**
     * Rewrites the issued code so it carries extra payload, the way the OpenID
     * Connect package already does for the nonce — the code is opaque to the
     * client and encrypted with our own key, so nothing leaks by doing so.
     *
     * @param  array<string, mixed>  $extra
     */
    protected function addToAuthCodePayload(ResponseTypeInterface $response, array $extra): ResponseTypeInterface
    {
        if (! $response instanceof RedirectResponse) {
            return $response;
        }

        $location = $response->generateHttpResponse($this->psr7Response())->getHeader('Location')[0];
        $parsed = parse_url($location);

        parse_str($parsed['query'] ?? '', $query);

        if (! isset($query['code'])) {
            return $response;
        }

        $payload = json_decode($this->decrypt($query['code']), true, 512, JSON_THROW_ON_ERROR);
        $query['code'] = $this->encrypt(json_encode(array_merge($payload, $extra), JSON_THROW_ON_ERROR));

        $parsed['query'] = http_build_query($query);
        $response->setRedirectUri($this->unparseUrl($parsed));

        return $response;
    }

    protected function rememberSsoSessionFromAuthCode(ServerRequestInterface $request): void
    {
        $code = $request->getParsedBody()['code'] ?? null;

        if ($code === null) {
            return;
        }

        $payload = json_decode($this->decrypt($code), true, 512, JSON_THROW_ON_ERROR);

        app(CurrentSsoSession::class)->remember($payload[SsoSession::SESSION_KEY] ?? null);
    }

    protected function rememberSsoSessionFromRefreshToken(ServerRequestInterface $request): void
    {
        $refreshToken = $request->getParsedBody()['refresh_token'] ?? null;

        if ($refreshToken === null) {
            return;
        }

        $payload = json_decode($this->decrypt($refreshToken), true, 512, JSON_THROW_ON_ERROR);

        app(CurrentSsoSession::class)->remember(
            Passport::token()->newQuery()
                ->whereKey($payload['access_token_id'] ?? '')
                ->value('sso_session_id'),
        );
    }

    private function psr7Response(): ResponseInterface
    {
        return new Response;
    }

    /** @param array<string, string> $parts */
    private function unparseUrl(array $parts): string
    {
        $scheme = isset($parts['scheme']) ? $parts['scheme'].'://' : '';
        $host = $parts['host'] ?? '';
        $port = isset($parts['port']) ? ':'.$parts['port'] : '';
        $path = $parts['path'] ?? '';
        $query = isset($parts['query']) ? '?'.$parts['query'] : '';
        $fragment = isset($parts['fragment']) ? '#'.$parts['fragment'] : '';

        return "{$scheme}{$host}{$port}{$path}{$query}{$fragment}";
    }
}
