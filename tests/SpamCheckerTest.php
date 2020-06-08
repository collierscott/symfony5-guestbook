<?php

namespace App\Tests;

use App\Entity\Comment;
use App\SpamChecker;
use phpDocumentor\Reflection\Types\Iterable_;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use function Symfony\Component\DependencyInjection\Loader\Configurator\iterator;

class SpamCheckerTest extends TestCase
{
    public function testSpamScoreWithInvalidRequest()
    {
        $comment = new Comment();
        $comment->setCreatedAtValue();

        $context = [];

        $client = new MockHttpClient(
            [
                new MockResponse('invalid',
                    ['response_headers' => ['x-akismet-debug-help: Invalid key']]
                )
            ]
        );

        $checker = new SpamChecker($client, 'abcde');

        $this->expectException(\RuntimeException::class);
        $this->expectException('Unable to check for spam: invalid (Invalid key).');

        $checker->getSpamScore($comment, $context);
    }

    /**
     * @dataProvider getComments
     *
     * @param int $expectedScore
     * @param ResponseInterface $response
     * @param Comment $comment
     * @param array $context
     */
    public function testSpamScore(int $expectedScore, ResponseInterface $response, Comment $comment, array $context)
    {
        $client = new MockHttpClient([$response]);
        $checker = new SpamChecker($client, 'c84d432d850d');

        try {
            $score = $checker->getSpamScore($comment, $context);
        } catch (ClientExceptionInterface $e) {
        } catch (RedirectionExceptionInterface $e) {
        } catch (ServerExceptionInterface $e) {
        } catch (TransportExceptionInterface $e) {
        }
        $this->assertSame($expectedScore, $score);
    }

    public function getComments() : iterable
    {
        $comment = new Comment();
        $comment->setCreatedAtValue();

        $context = [];

        $response = new MockResponse('', ['response_headers' => 'x-akismet-pro-tip: discard']);
        yield 'blatent-spam' => [2, $response, $comment, $context];

        $response = new MockResponse('true');
        yield 'spam' => [1, $response, $comment, $context];

        $response = new MockResponse('false');
        yield 'ham' => [0, $response, $comment, $context];
    }

}
