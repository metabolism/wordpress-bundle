<?php

namespace Metabolism\WordpressBundle\Tests;

use Metabolism\WordpressBundle\Entity\File;
use Metabolism\WordpressBundle\Entity\Image;
use Metabolism\WordpressBundle\Entity\Menu;
use Metabolism\WordpressBundle\Entity\Post;
use Metabolism\WordpressBundle\Entity\Term;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class EntityTest extends KernelTestCase
{
    private $base_url = 'http://localhost';
    public function testMenu()
    {
        self::bootKernel();

        $footer = new Menu('footer');
        $items = $footer->getItems();

        $this->assertEquals('Footer test', $footer->getTitle());
        $this->assertCount(7, $items);

        $this->assertEquals('Sample Page', $items[0]->getTitle());
        $this->assertEquals($this->base_url.'/sample-page', $items[0]->getLink());

        $this->assertEquals('Test item', $items[1]->getTitle());
        $this->assertEquals($this->base_url.'/item/test-item', $items[1]->getLink());

        $this->assertEquals('Uncategorized', $items[2]->getTitle());
        $this->assertEquals($this->base_url.'/category/uncategorized', $items[2]->getLink());

        $this->assertEquals('Test guide', $items[4]->getTitle());
        $this->assertEquals($this->base_url.'/guide/test-guide', $items[4]->getLink());
        $this->assertEquals('_blank', $items[4]->getTarget());

        $this->assertEquals('All guides', $items[5]->getTitle());
        $this->assertEquals($this->base_url.'/guide', $items[5]->getLink());

        $children = $items[6]->getChildren();
        $this->assertCount(1, $children);
    }
    public function testTerm()
    {
        self::bootKernel();

        $term = new Term(6);

        $this->assertEquals('Primary', $term->getTitle());
    }
    public function testPost()
    {
        self::bootKernel();

        $post = new Post(9);

        $term = $post->getTerm('category');
        $terms = $post->getTerms('category');

        $this->assertEquals('Test', $post->getTitle());
        $this->assertEquals('post', $post->getType());
        $this->assertEquals('This is the excerpt', $post->getExcerpt());
        $this->assertEquals("\n<p></p>\n<p class=\"has-luminous-vivid-orange-to-vivid-red-gradient-background has-background has-small-font-size\">Hello</p>\n", $post->getContent());

        $this->assertEquals('Primary', $term->getTitle());

        $this->assertCount(3, $terms);

        $this->assertEquals($this->base_url.'/uploads/2023/07/placeholder-50x50-c-28x45.png', $post->getThumbnail(50,50));

        $this->assertEquals('Lorem ipsum', $post->getCustomField('text'));
        $this->assertInstanceOf(Image::class, $post->getCustomField('image'));
        $this->assertInstanceOf(Post::class, $post->getCustomField('post'));
        $this->assertInstanceOf(Term::class, $post->getCustomField('term'));
        $this->assertInstanceOf(File::class, $post->getCustomField('file'));
    }
    public function testImage()
    {
        self::bootKernel();

        $thumbnail = new Image(41);

        $this->assertEquals('alt text', $thumbnail->getAlt());
        $this->assertEquals('placeholder', $thumbnail->getTitle());
        $this->assertEquals('image/png', $thumbnail->getMimeType());
        $this->assertEquals('png', $thumbnail->getExtension());
        $this->assertEquals(6.3466796875, $thumbnail->getFilesize());
        $this->assertEquals('2023-07-18', $thumbnail->getDate('Y-m-d'));
        $this->assertEquals(800, $thumbnail->getHeight());
        $this->assertEquals(1200, $thumbnail->getWidth());
        $this->assertEquals($this->base_url.'/uploads/2023/07/placeholder.png', $thumbnail->getLink());
        $this->assertEquals($this->base_url.'/uploads/2023/07/placeholder-50x50-c-28x45.png', $thumbnail->resize(50,50));
        $this->assertEquals('https://placehold.jp/50x50.png', $thumbnail->placeholder(50,50));
        $this->assertEquals('<picture><source srcset="'.$this->base_url.'/uploads/2023/07/placeholder-50x50-c-28x45.webp" type="image/webp"/><img src="'.$this->base_url.'/uploads/2023/07/placeholder-50x50-c-28x45.png" alt="alt text" loading="lazy" width="50" height="50"/></picture>', $thumbnail->picture(50,50));
        $this->assertEquals('<picture><source media="(max-width:600px)" srcset="'.$this->base_url.'/uploads/2023/07/placeholder-20x20-c-28x45.webp" type="image/webp"/><source media="(max-width:600px)" srcset="'.$this->base_url.'/uploads/2023/07/placeholder-20x20-c-28x45.png" type="image/png"/><source srcset="'.$this->base_url.'/uploads/2023/07/placeholder-50x50-c-28x45.webp" type="image/webp"/><img src="'.$this->base_url.'/uploads/2023/07/placeholder-50x50-c-28x45.png" alt="alt text" loading="lazy" width="50" height="50"/></picture>', $thumbnail->picture(50,50,['max-width:600px'=>[20,20]]));
        $this->assertEquals('Lorem ipsum', $thumbnail->getCustomField('text'));
    }
    public function testFile()
    {
        self::bootKernel();

        $file = new File(42);

        $this->assertEquals('sample', $file->getTitle());
        $this->assertEquals('application/pdf', $file->getMimeType());
        $this->assertEquals('pdf', $file->getExtension());
        $this->assertEquals(2.95703125, $file->getFilesize());
        $this->assertEquals('2023-07-18', $file->getDate('Y-m-d'));
        $this->assertEquals($this->base_url.'/uploads/2023/07/sample.pdf', $file->getLink());
        $this->assertEquals('Lorem ipsum', $file->getCustomField('text'));
    }
}