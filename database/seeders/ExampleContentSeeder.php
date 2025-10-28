<?php

declare(strict_types=1);

namespace FrankenCms\Database\Seeders;

use FrankenCms\Enums\PostStatus;
use FrankenCms\Models\Menu;
use FrankenCms\Models\Post;
use FrankenCms\Models\Taxonomy;
use FrankenCms\Models\Term;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ExampleContentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🧟 Igor: Starting to build example content, Master...');

        // Create taxonomies and terms
        $categories = $this->createTaxonomiesAndTerms();

        // Create pages
        $this->createPages();

        // Create blog posts
        $this->createBlogPosts($categories);

        // Create menu
        $this->createMainMenu();

        $this->command->info('👨‍⚕️ Dr. Frankenstein: Magnificent! The example content is ALIVE!');
    }

    protected function createTaxonomiesAndTerms(): array
    {
        $this->command->info('🔧 Creating categories and tags...');

        // Get or create category taxonomy
        $categoryTaxonomy = Taxonomy::firstOrCreate(
            ['name' => 'category'],
            [
                'slug'     => 'category',
                'singular' => 'Category',
                'plural'   => 'Categories',
            ]
        );

        // Get or create tag taxonomy
        $tagTaxonomy = Taxonomy::firstOrCreate(
            ['name' => 'tag'],
            [
                'slug'     => 'tag',
                'singular' => 'Tag',
                'plural'   => 'Tags',
            ]
        );

        // Create example categories
        $categories = [
            'News'              => 'Latest updates and announcements',
            'Tutorials'         => 'Step-by-step guides and how-tos',
            'Behind the Scenes' => 'A peek into our laboratory',
        ];

        $createdCategories = [];
        foreach ($categories as $name => $description) {
            $category = Term::firstOrCreate(
                [
                    'taxonomy_id' => $categoryTaxonomy->id,
                    'slug'        => Str::slug($name),
                ],
                [
                    'name'        => $name,
                    'description' => $description,
                ]
            );
            $createdCategories[$name] = $category;
        }

        // Create example tags
        $tags = ['FrankenCMS', 'Laravel', 'FilamentPHP', 'Gothic', 'Monster'];
        foreach ($tags as $name) {
            Term::firstOrCreate(
                [
                    'taxonomy_id' => $tagTaxonomy->id,
                    'slug'        => Str::slug($name),
                ],
                [
                    'name'        => $name,
                    'description' => null,
                ]
            );
        }

        return $createdCategories;
    }

    protected function createPages(): void
    {
        $this->command->info('📄 Creating example pages...');

        $user = config('franken-cms.models.user')::first();

        // Home Page
        Post::firstOrCreate(
            [
                'post_type' => 'page',
                'post_slug' => 'home',
            ],
            [
                'post_title'   => 'Home',
                'post_content' => [
                    'content' => '<p>Welcome to FrankenCMS - A WordPress Alternative Built with Laravel!</p>',
                ],
                'post_status'       => PostStatus::PUBLISH->value,
                'post_published_at' => now(),
                'post_author_id'    => $user?->id,
                'route_name'        => 'home',
            ]
        )->setMeta('template', 'page-home');

        // About Page
        Post::firstOrCreate(
            [
                'post_type' => 'page',
                'post_slug' => 'about',
            ],
            [
                'post_title'   => 'About',
                'post_content' => [
                    'content' => '<p>Learn more about FrankenCMS and the mad scientists behind it!</p>',
                ],
                'post_status'       => PostStatus::PUBLISH->value,
                'post_published_at' => now(),
                'post_author_id'    => $user?->id,
            ]
        )->setMeta('template', 'page-about');

        // Contact Page
        Post::firstOrCreate(
            [
                'post_type' => 'page',
                'post_slug' => 'contact',
            ],
            [
                'post_title'   => 'Contact',
                'post_content' => [
                    'content' => '<p>Get in touch with us. We don\'t bite... much.</p>',
                ],
                'post_status'       => PostStatus::PUBLISH->value,
                'post_published_at' => now(),
                'post_author_id'    => $user?->id,
            ]
        )->setMeta('template', 'page-contact');

        // Blog Index Page
        Post::firstOrCreate(
            [
                'post_type' => 'page',
                'post_slug' => 'blog',
            ],
            [
                'post_title'   => 'Blog',
                'post_content' => [
                    'content' => '<p>Tales from the laboratory and beyond.</p>',
                ],
                'post_status'       => PostStatus::PUBLISH->value,
                'post_published_at' => now(),
                'post_author_id'    => $user?->id,
            ]
        )->setMeta('template', 'page-blog');
    }

    protected function createBlogPosts(array $categories): void
    {
        $this->command->info('✍️  Creating example blog posts...');

        $user = config('franken-cms.models.user')::first();

        $posts = [
            [
                'title'    => 'Welcome to FrankenCMS!',
                'content'  => '<h2>It\'s Alive!</h2><p>Welcome to FrankenCMS, a modern WordPress alternative built with the power of Laravel and FilamentPHP. Like Dr. Frankenstein\'s creation, we\'ve assembled the best parts from the Laravel ecosystem to create something truly magnificent.</p><h3>Why FrankenCMS?</h3><p>We believe content management should be powerful, flexible, and fun. FrankenCMS brings together the elegance of Laravel with the user-friendly admin interface of Filament to create a CMS that developers and content creators both love.</p>',
                'category' => 'News',
            ],
            [
                'title'    => 'Getting Started with FrankenCMS',
                'content'  => '<h2>Your First Steps</h2><p>Getting started with FrankenCMS is frighteningly easy! Simply install the package via Composer and run our theatrical installer.</p><h3>Installation</h3><pre><code>composer require frankencms/franken-cms\nphp artisan franken-cms:install</code></pre><p>Igor will guide you through the installation process with help from Dr. Frankenstein himself!</p>',
                'category' => 'Tutorials',
            ],
            [
                'title'    => 'Behind the Laboratory Doors',
                'content'  => '<h2>A Peek Inside</h2><p>Ever wondered what happens in our laboratory? The creation of FrankenCMS was no accident. It took careful planning, countless experiments, and more than a few lightning storms to bring this project to life.</p><p>We started with a simple question: What if WordPress was built with modern PHP? The answer became FrankenCMS.</p>',
                'category' => 'Behind the Scenes',
            ],
        ];

        foreach ($posts as $index => $postData) {
            $post = Post::firstOrCreate(
                [
                    'post_type' => 'post',
                    'post_slug' => Str::slug($postData['title']),
                ],
                [
                    'post_title'   => $postData['title'],
                    'post_content' => [
                        'content' => $postData['content'],
                    ],
                    'post_status'       => PostStatus::PUBLISH->value,
                    'post_published_at' => now()->subDays(count($posts) - $index),
                    'post_author_id'    => $user?->id,
                ]
            );

            // Attach category
            if (isset($categories[$postData['category']])) {
                $post->terms()->syncWithoutDetaching([$categories[$postData['category']]->id]);
            }

            // Attach some tags
            $tagTaxonomy = Taxonomy::where('name', 'tag')->first();
            if ($tagTaxonomy) {
                $tags = Term::where('taxonomy_id', $tagTaxonomy->id)->limit(2)->get();
                $post->terms()->syncWithoutDetaching($tags->pluck('id'));
            }
        }
    }

    protected function createMainMenu(): void
    {
        $this->command->info('🔗 Creating main navigation menu...');

        // Create or get main menu
        $mainMenu = Menu::firstOrCreate(
            ['slug' => 'main-navigation'],
            [
                'name' => 'Main Navigation',
            ]
        );

        // Get pages
        $homePage = Post::where('post_slug', 'home')->first();
        $aboutPage = Post::where('post_slug', 'about')->first();
        $blogPage = Post::where('post_slug', 'blog')->first();
        $contactPage = Post::where('post_slug', 'contact')->first();

        // Create menu structure
        $menuItems = [
            [
                'label'         => 'Home',
                'linkable_type' => Post::class,
                'linkable_id'   => $homePage?->id,
                'url'           => $homePage?->url,
                'sort_order'    => 0,
                'parent_id'     => null,
            ],
            [
                'label'         => 'About',
                'linkable_type' => Post::class,
                'linkable_id'   => $aboutPage?->id,
                'url'           => $aboutPage?->url,
                'sort_order'    => 1,
                'parent_id'     => null,
            ],
            [
                'label'         => 'Blog',
                'linkable_type' => Post::class,
                'linkable_id'   => $blogPage?->id,
                'url'           => $blogPage?->url,
                'sort_order'    => 2,
                'parent_id'     => null,
            ],
            [
                'label'         => 'Contact',
                'linkable_type' => Post::class,
                'linkable_id'   => $contactPage?->id,
                'url'           => $contactPage?->url,
                'sort_order'    => 3,
                'parent_id'     => null,
            ],
        ];

        foreach ($menuItems as $item) {
            $mainMenu->allMenuItems()->firstOrCreate(
                [
                    'label'      => $item['label'],
                    'sort_order' => $item['sort_order'],
                ],
                $item
            );
        }
    }
}
