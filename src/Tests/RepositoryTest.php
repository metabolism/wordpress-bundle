<?php

namespace Metabolism\WordpressBundle\Tests;

use Metabolism\WordpressBundle\Entity\Blog;
use Metabolism\WordpressBundle\Repository\PostRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class RepositoryTest extends KernelTestCase
{
    private $base_url = 'http://localhost';

    public function testPostRepository()
    {
        self::bootKernel();

        $postRepository = new PostRepository();

        $post = $postRepository->find(9);
        $this->assertEquals('Test', $post->getTitle());
    }
}