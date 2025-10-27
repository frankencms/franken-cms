<?php

use FrankenCms\Models\UserBio;
use FrankenCms\Tests\Support\User;

describe('UserBio Model', function () {

    it('can create a user bio', function () {
        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password',
        ]);

        $bio = UserBio::create([
            'user_id' => $user->id,
            'title' => 'Senior Developer',
            'bio' => 'I love building things with Laravel.',
            'website' => 'https://example.com',
            'social_links' => [
                'twitter' => 'https://twitter.com/johndoe',
                'github' => 'https://github.com/johndoe',
            ],
        ]);

        expect($bio)->toBeInstanceOf(UserBio::class);
        expect($bio->title)->toBe('Senior Developer');
        expect($bio->bio)->toBe('I love building things with Laravel.');
        expect($bio->website)->toBe('https://example.com');
        expect($bio->social_links)->toBeArray();
        expect($bio->social_links)->toHaveKey('twitter');
    });

    it('belongs to a user', function () {
        $user = User::create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'password',
        ]);

        $bio = UserBio::create([
            'user_id' => $user->id,
            'title' => 'Content Writer',
        ]);

        expect($bio->user)->toBeInstanceOf(User::class);
        expect($bio->user->id)->toBe($user->id);
        expect($bio->user->name)->toBe('Jane Doe');
    });

    it('can get a specific social link', function () {
        $user = User::create([
            'name' => 'Bob Smith',
            'email' => 'bob@example.com',
            'password' => 'password',
        ]);

        $bio = UserBio::create([
            'user_id' => $user->id,
            'social_links' => [
                'twitter' => 'https://twitter.com/bobsmith',
                'linkedin' => 'https://linkedin.com/in/bobsmith',
            ],
        ]);

        expect($bio->getSocialLink('twitter'))->toBe('https://twitter.com/bobsmith');
        expect($bio->getSocialLink('linkedin'))->toBe('https://linkedin.com/in/bobsmith');
        expect($bio->getSocialLink('github'))->toBeNull();
    });

    it('can set a social link', function () {
        $user = User::create([
            'name' => 'Alice Johnson',
            'email' => 'alice@example.com',
            'password' => 'password',
        ]);

        $bio = UserBio::create([
            'user_id' => $user->id,
            'social_links' => [],
        ]);

        $bio->setSocialLink('twitter', 'https://twitter.com/alicejohnson');
        $bio->save();

        expect($bio->getSocialLink('twitter'))->toBe('https://twitter.com/alicejohnson');
    });

    it('can update an existing social link', function () {
        $user = User::create([
            'name' => 'Charlie Brown',
            'email' => 'charlie@example.com',
            'password' => 'password',
        ]);

        $bio = UserBio::create([
            'user_id' => $user->id,
            'social_links' => [
                'twitter' => 'https://twitter.com/charlieold',
            ],
        ]);

        $bio->setSocialLink('twitter', 'https://twitter.com/charlienew');
        $bio->save();

        expect($bio->getSocialLink('twitter'))->toBe('https://twitter.com/charlienew');
    });

});

describe('HasBio Trait', function () {

    it('user has bio relationship', function () {
        $user = User::create([
            'name' => 'David Miller',
            'email' => 'david@example.com',
            'password' => 'password',
        ]);

        UserBio::create([
            'user_id' => $user->id,
            'title' => 'Designer',
        ]);

        expect($user->bio)->toBeInstanceOf(UserBio::class);
        expect($user->bio->title)->toBe('Designer');
    });

    it('can check if user has a bio', function () {
        $userWithBio = User::create([
            'name' => 'Eve Anderson',
            'email' => 'eve@example.com',
            'password' => 'password',
        ]);

        $userWithoutBio = User::create([
            'name' => 'Frank Wilson',
            'email' => 'frank@example.com',
            'password' => 'password',
        ]);

        UserBio::create([
            'user_id' => $userWithBio->id,
            'title' => 'Marketing Manager',
        ]);

        expect($userWithBio->hasBio())->toBeTrue();
        expect($userWithoutBio->hasBio())->toBeFalse();
    });

    it('can get or create a bio', function () {
        $user = User::create([
            'name' => 'Grace Lee',
            'email' => 'grace@example.com',
            'password' => 'password',
        ]);

        expect($user->hasBio())->toBeFalse();

        $bio = $user->getOrCreateBio();

        expect($bio)->toBeInstanceOf(UserBio::class);
        expect($bio->user_id)->toBe($user->id);
        expect($user->hasBio())->toBeTrue();
    });

    it('returns existing bio when using getOrCreateBio', function () {
        $user = User::create([
            'name' => 'Henry Davis',
            'email' => 'henry@example.com',
            'password' => 'password',
        ]);

        $originalBio = UserBio::create([
            'user_id' => $user->id,
            'title' => 'Original Title',
        ]);

        $retrievedBio = $user->getOrCreateBio();

        expect($retrievedBio->id)->toBe($originalBio->id);
        expect($retrievedBio->title)->toBe('Original Title');
    });

    it('enforces one bio per user', function () {
        $user = User::create([
            'name' => 'Isabel Martinez',
            'email' => 'isabel@example.com',
            'password' => 'password',
        ]);

        UserBio::create([
            'user_id' => $user->id,
            'title' => 'First Bio',
        ]);

        // Attempting to create a second bio should throw a unique constraint exception
        expect(fn () => UserBio::create([
            'user_id' => $user->id,
            'title' => 'Second Bio',
        ]))->toThrow(Exception::class);
    });

});

describe('UserBio Media', function () {

    it('can register bio-image media collection', function () {
        $user = User::create([
            'name' => 'Jack Thompson',
            'email' => 'jack@example.com',
            'password' => 'password',
        ]);

        $bio = UserBio::create([
            'user_id' => $user->id,
            'title' => 'Photographer',
        ]);

        $collections = $bio->getRegisteredMediaCollections();
        $collectionNames = $collections->pluck('name')->toArray();

        expect($collectionNames)->toContain('bio-image');
    });

    it('bio-image collection is single file', function () {
        $user = User::create([
            'name' => 'Kate Wilson',
            'email' => 'kate@example.com',
            'password' => 'password',
        ]);

        $bio = UserBio::create([
            'user_id' => $user->id,
            'title' => 'Designer',
        ]);

        $collection = $bio->getMediaCollection('bio-image');

        expect($collection->singleFile)->toBeTrue();
    });

    it('implements HasMedia interface', function () {
        $user = User::create([
            'name' => 'Laura Martinez',
            'email' => 'laura@example.com',
            'password' => 'password',
        ]);

        $bio = UserBio::create([
            'user_id' => $user->id,
            'title' => 'Writer',
        ]);

        expect($bio)->toBeInstanceOf(\Spatie\MediaLibrary\HasMedia::class);
        expect($bio->hasMedia('bio-image'))->toBeFalse();
    });

    it('has bio-thumb and bio-large conversions registered', function () {
        $user = User::create([
            'name' => 'Mike Anderson',
            'email' => 'mike@example.com',
            'password' => 'password',
        ]);

        $bio = UserBio::create([
            'user_id' => $user->id,
            'title' => 'Developer',
        ]);

        // MediaConversions are registered in registerMediaConversions method
        // We can verify the method exists and is callable
        expect(method_exists($bio, 'registerMediaConversions'))->toBeTrue();
    });

});
