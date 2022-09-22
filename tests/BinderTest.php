<?php

namespace Bindto;

use Bindto\Mapper\MapperStrategy;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validation;

class BinderTest extends TestCase
{
    /**
     * @test
     */
    public function shouldBindADTOFromSymfonyRequestWithPost()
    {
        $request = $this->createRequest();

        $binder = Binder::createDefaultBinder();
        $bindResult = $binder->bind($request, \Bindto\Fixtures\DTOCreatePage::class);

        $this->assertTrue($bindResult->isValid(), $bindResult->getViolations());
    }

    /**
     * @test
     */
    public function shouldBindAndValidatePartiallyAPatch()
    {
        $vars = [
            'body' => 'only the body is patched',
        ];

        $request = Request::create('http://test.com/foo', 'PATCH', $vars, [], [], []);

        $binder = Binder::createDefaultBinder();
        $bindResult = $binder->bind($request, \Bindto\Fixtures\DTOCreatePage::class);

        $this->assertTrue($bindResult->isValid(), $bindResult->getViolations());
        $this->assertEquals($vars['body'], $bindResult->getData()->body);
    }

    /**
     * @test
     */
    public function shouldReturnAViolation()
    {
        $vars = [
            'body' => 'Lorem ipsum dolor sit amet, consectetuer adipi',
            'seoTitle' => 'slug of lorem ',
            'seoDescription' => 'Lorem ipsum dolor sit amet, consectetuer adipi',
        ];

        $request = Request::create('http://test.com/foo', 'POST', $vars, [], [], []);

        $binder = Binder::createDefaultBinder();
        $bindResult = $binder->bind($request, \Bindto\Fixtures\DTOCreatePage::class);

        $this->assertCount(1, $bindResult->getViolations());
    }

    /**
     * @test
     */
    public function shouldUseTheFallback()
    {
        $vars = [
            'title' => 'yes',
            'body' => 'Lorem ipsum dolor sit amet, consectetuer adipi',
            'seoTitle' => 'slug of lorem ',
            'seoDescription' => 'Lorem ipsum dolor sit amet, consectetuer adipi',
        ];

        $binder = Binder::createDefaultBinder();
        $bindResult = $binder->bind($vars, \Bindto\Fixtures\DTOCreatePage::class);

        $this->assertTrue($bindResult->isValid(), $bindResult->getViolations());
    }

    /**
     * @test
     * @expectedException \Bindto\Exception\MapperNotFoundException
     */
    public function shouldRaiseException()
    {
        $vars = [
            'title' => 'yes',
            'body' => 'Lorem ipsum dolor sit amet, consectetuer adipi',
            'seoTitle' => 'slug of lorem ',
            'seoDescription' => 'Lorem ipsum dolor sit amet, consectetuer adipi',
        ];

        $validator = Validation::createValidatorBuilder()
            ->enableAnnotationMapping()
            ->getValidator();
        $mapper = new MapperStrategy();
        $binder = new Binder($validator, $mapper);
        $binder->bind($vars, \Bindto\Fixtures\DTOCreatePage::class);
    }

    /**
     * @return Request
     */
    private function createRequest()
    {
        $vars = [
            'title' => 'my-slug',
            'body' => 'Lorem ipsum dolor sit amet, consectetuer adipi',
            'seoTitle' => 'slug of lorem ',
            'seoDescription' => 'Lorem ipsum dolor sit amet, consectetuer adipi',
        ];

        $request = Request::create('http://test.com/foo', 'POST', $vars, [], [], []);

        return $request;
    }
}
