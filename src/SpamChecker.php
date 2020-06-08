<?php

namespace App;

use App\Entity\Comment;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SpamChecker
{
    /**
     * @var HttpClientInterface $client
     */
    private $client;

    private $akismetKey;

    /**
     * @var string $endpoint
     */
    private $endpoint;

    public function __construct(HttpClientInterface $client, string $akismetKey)
    {
        $this->client = $client;
        $this->akismetKey = $akismetKey;

        $uri = sprintf('https://%s.rest.akismet.com/1.1/comment-check', $akismetKey);
        $this->endpoint = $uri;
    }

    /**
     * Use special akismet-guaranteed-spam@example.com email address to force spam result.
     *
     * @param Comment $comment
     * @param array $context
     *
     * @return int Spam score: 0 = not spam, 1 = maybe spam, 2 = spam
     *
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function getSpamScore(Comment $comment, array $context) : int
    {
        // $isVerified = $this->akismetVerifyKey($this->akismetKey, 'http://s5-guestbook.localhost/');

        $response = $this->client->request('POST', $this->endpoint, [
            'body' => array_merge($context, [
                'blog' => 'https://guestbook.localhost',
                'comment_type' => 'comment',
                'comment_author' => $comment->getAuthor(),
                'comment_author_email' => $comment->getEmail(),
                'comment_content' => $comment->getText(),
                'comment_date_gmt' => $comment->getCreatedAt()->format('c'),
                'blog_lang' => 'en',
                'blog_charset' => 'UTF-8',
                'is_test' => true,
            ]),
        ]);

        $headers = $response->getHeaders();

        if('discard' === ($headers['x-akismet-pro-tip'][0] ?? '')) {
            return 2;
        }

        $content = $response->getContent();

        if (isset($headers['x-akismet-debug-help'][0])) {
            throw new \RuntimeException((sprintf('Unable to check for spam: %s (%s)', $content, $headers['x-akismet-debug-help'][0])));
        }

        return 'true' === $content ? 1 : 0;
    }

    function akismetVerifyKey( $key, $blog ) {
        $blog = urlencode($blog);
        $request = 'key='. $key .'&blog='. $blog;
        $host = $http_host = 'rest.akismet.com';
        $path = '/1.1/verify-key';
        $port = 443;
        $akismet_ua = "WordPress/4.4.1 | Akismet/3.1.7";
        $content_length = strlen( $request );
        $http_request  = "POST $path HTTP/1.0\r\n";
        $http_request .= "Host: $host\r\n";
        $http_request .= "Content-Type: application/x-www-form-urlencoded\r\n";
        $http_request .= "Content-Length: {$content_length}\r\n";
        $http_request .= "User-Agent: {$akismet_ua}\r\n";
        $http_request .= "\r\n";
        $http_request .= $request;
        $response = '';
        if( false != ( $fs = @fsockopen( 'ssl://' . $http_host, $port, $errno, $errstr, 10 ) ) ) {

            fwrite( $fs, $http_request );

            while ( !feof( $fs ) )
                $response .= fgets( $fs, 1160 ); // One TCP-IP packet
            fclose( $fs );

            $response = explode( "\r\n\r\n", $response, 2 );
        }

        if ( 'valid' == $response[1] )
            return true;
        else
            return false;
    }
}