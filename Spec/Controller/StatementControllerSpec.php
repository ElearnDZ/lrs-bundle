<?php

namespace Spec\XApi\LrsBundle\Controller;

use PhpSpec\ObjectBehavior;
use Rhumsaa\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Request;
use Xabbuh\XApi\Common\Exception\NotFoundException;
use Xabbuh\XApi\DataFixtures\StatementFixtures;
use Xabbuh\XApi\Model\StatementId;
use XApi\Repository\Api\StatementRepositoryInterface;

class StatementControllerSpec extends ObjectBehavior
{
    function let(StatementRepositoryInterface $repository)
    {
        $this->beConstructedWith($repository);
    }

    function it_returns_a_400_response_if_a_statement_id_is_not_part_of_a_put_request()
    {
        $statement = StatementFixtures::getTypicalStatement();
        $request = new Request();
        $response = $this->putStatement($request, $statement);

        $response->shouldHaveType('Symfony\Component\HttpFoundation\Response');
        $response->getStatusCode()->shouldReturn(400);
    }

    function it_returns_a_400_response_if_the_given_statement_id_as_part_of_a_put_request_is_not_a_valid_uuid()
    {
        $statement = StatementFixtures::getTypicalStatement();
        $request = new Request();
        $request->query->set('statementId', 'invalid-uuid');
        $response = $this->putStatement($request, $statement);

        $response->shouldHaveType('Symfony\Component\HttpFoundation\Response');
        $response->getStatusCode()->shouldReturn(400);
    }

    function it_stores_a_statement_and_returns_a_204_response_if_the_statement_did_not_exist_before(StatementRepositoryInterface $repository)
    {
        $statement = StatementFixtures::getTypicalStatement();
        $request = new Request();
        $request->query->set('statementId', $statement->getId()->getValue());

        $repository->findStatementById($statement->getId())->willThrow(new NotFoundException(''));
        $repository->storeStatement($statement, true)->shouldBeCalled();

        $response = $this->putStatement($request, $statement);

        $response->shouldHaveType('Symfony\Component\HttpFoundation\Response');
        $response->getStatusCode()->shouldReturn(204);
    }

    function it_returns_a_409_response_if_the_id_parameter_and_the_statement_id_do_not_match_during_a_put_request()
    {
        $statement = StatementFixtures::getTypicalStatement();
        $statementId = StatementId::fromUuid(Uuid::uuid4());
        $request = new Request();
        $request->query->set('statementId', $statementId->getValue());

        $response = $this->putStatement($request, $statement);

        $response->shouldHaveType('Symfony\Component\HttpFoundation\Response');
        $response->getStatusCode()->shouldReturn(409);
    }

    function it_uses_id_parameter_in_put_request_if_statement_id_is_null(StatementRepositoryInterface $repository)
    {
        $statement = StatementFixtures::getTypicalStatement();
        $statementId = $statement->getId();
        $statement = $statement->withId(null);
        $request = new Request();
        $request->query->set('statementId', $statementId->getValue());

        $repository->findStatementById($statementId)->willReturn($statement);
        $repository->findStatementById($statementId)->shouldBeCalled();

        $this->putStatement($request, $statement);
    }

    function it_does_not_override_an_existing_statement(StatementRepositoryInterface $repository)
    {
        $statement = StatementFixtures::getTypicalStatement();
        $request = new Request();
        $request->query->set('statementId', $statement->getId()->getValue());

        $repository->findStatementById($statement->getId())->willReturn($statement);
        $repository->storeStatement($statement, true)->shouldNotBeCalled();

        $this->putStatement($request, $statement);
    }

    function it_returns_a_409_response_if_an_existing_statement_with_the_same_id_is_not_equal_during_a_put_request(StatementRepositoryInterface $repository)
    {
        $statement = StatementFixtures::getTypicalStatement();
        $existingStatement = StatementFixtures::getAttachmentStatement()->withId($statement->getId());
        $request = new Request();
        $request->query->set('statementId', $statement->getId()->getValue());

        $repository->findStatementById($statement->getId())->willReturn($existingStatement);

        $response = $this->putStatement($request, $statement);

        $response->shouldHaveType('Symfony\Component\HttpFoundation\Response');
        $response->getStatusCode()->shouldReturn(409);
    }
}