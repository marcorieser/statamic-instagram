# Statamic Instagram Business API

> Statamic Instagram Business API lets you fetch data via the Instagram Business API into your Statamic site.

## Features

- Fetch Instagram posts via Meta's [Instagram Business API](https://developers.facebook.com/docs/instagram-platform)
- Fetch Instagram profile data via Meta's [Instagram Business API](https://developers.facebook.com/docs/instagram-platform)
- Supports Instagram login and Unified login
- Auto refreshing of Instagram login Access Tokens

## How to Install

You can install this addon via Composer:

``` bash
composer require marcorieser/statamic-instagram
```

## How to Use

### Installation

- Install the addon
- Create an Access Token and add it to your `.env` file, see [Creating a Meta App / an Access Token](#creating-a-meta-app--an-access-token). If you already have one, add it as `INSTAGRAM_ACCESS_TOKEN`.
- (Optionally) Publish the addon config by running `php artisan vendor:publish --tag=statamic-instagram-config`

### Creating a Meta App / an Access Token

Since late October 2026, new Meta apps no longer offer `API setup with Instagram login`.
If your app only offers `API setup with Unified login`, follow the Unified login steps. Otherwise use the Instagram login steps.

#### Unified login

You need an Instagram Business or Creator account and a Meta business portfolio (free).

1. Create an app at https://developers.facebook.com with the use case `Manage messaging & content on Instagram` and connect it to your business portfolio.
2. Open `Use cases` > `Customize` and select `API setup with Unified login`.
3. Create a configuration with the access token type `System user access token`, expiration `Never`, your Instagram account as asset and the permission `instagram_basic`.
4. Generate the token and add it to your `.env` file:
    ```
    INSTAGRAM_LOGIN_METHOD=unified
    INSTAGRAM_ACCESS_TOKEN=your-token
    ```
5. Only if the token has more than one Instagram account assigned: set `INSTAGRAM_USER_ID`. You find the IDs at `https://graph.facebook.com/v26.0/me/assigned_instagram_accounts?access_token=your-token`.

If the API answers with a permission error, add `pages_show_list`, `pages_read_engagement` and `business_management` to the configuration.
Unified tokens never expire, so the addon does not refresh them.
Meta changes its setup screens regularly. If a label differs, follow Meta's [documentation](https://developers.facebook.com/documentation/instagram-platform).

#### Instagram login

You need an Instagram Business or Creator account. Only available for apps that still offer `API setup with Instagram login`.

1. Create an app at https://developers.facebook.com (use case `Other`, app type `Business`) and add `Instagram` as a product.
2. Open `API setup with Instagram login`. If your Instagram account is not selectable, add it as an `Instagram Tester` under `App roles` > `Roles` and accept the invitation in the Instagram app.
3. At `1. Generate access tokens`, add your Instagram account and generate a token with the permission `instagram_business_basic`.
4. Add the token to your `.env` file:
    ```
    INSTAGRAM_LOGIN_METHOD=instagram
    INSTAGRAM_ACCESS_TOKEN=your-token
    ```

The token is valid for 60 days. The addon refreshes it automatically, see [Refreshing Tokens](#refreshing-tokens).
For more details see Meta's [documentation](https://developers.facebook.com/docs/instagram-platform/instagram-api-with-instagram-login/create-a-meta-app-with-instagram).

### Display the feed

There is a `{{ instagram:feed }}` tag, that fetches the media from the API and returns them as an array.

- The `limit` parameter defaults to `12`.
- The `handle` parameter defaults to the first account in the config.

```antlers
{{ instagram:feed limit="12" handle="rickastley" }}
    {{ id }}
    {{ caption }}
    {{ comments_count }}
    {{ is_shared_to_feed }}
    {{ like_count }}
    {{ media_product_type }}
    {{ media_type }}
    {{ media_url }}
    {{ permalink }}
    {{ thumbnail_url }}
    {{ timestamp }}
    {{ username }}
    
    {{ children }}
        {{ id }}
        {{ comments_count }}
        {{ like_count }}
        {{ media_product_type }}
        {{ media_type }}
        {{ media_url }}
        {{ permalink }}
        {{ thumbnail_url }}
        {{ timestamp }}
        {{ username }}
    {{ /children }}
{{ /instagram:feed }}
```

### Display a specific media

There is a `{{ instagram:media }}` tag, that fetches just one specific media.

- The `id` parameter is required.
- The `handle` parameter defaults to the first account in the config.

```antlers
{{ instagram:media id="18051623968824939" handle="rickastley" }}
    {{ id }}
    {{ caption }}
    {{ comments_count }}
    {{ is_shared_to_feed }}
    {{ like_count }}
    {{ media_product_type }}
    {{ media_type }}
    {{ media_url }}
    {{ permalink }}
    {{ thumbnail_url }}
    {{ timestamp }}
    {{ username }}

    {{ children }}
        {{ id }}
        {{ comments_count }}
        {{ like_count }}
        {{ media_product_type }}
        {{ media_type }}
        {{ media_url }}
        {{ permalink }}
        {{ thumbnail_url }}
        {{ timestamp }}
        {{ username }}
    {{ /children }}
{{ /instagram:media }}
```

### Display profile data

There is a `{{ instagram:profile }}` tag, that fetches the profile information from the API and returns them as an array.

- The `handle` parameter defaults to the first account in the config.

```antlers
{{ instagram:profile handle="rickastley" }}
    {{ id }}
    {{ followers_count }}
    {{ follows_count }}
    {{ media_count }}
    {{ biography }}
    {{ name }}
    {{ profile_picture_url }}
    {{ username }}
    {{ website }}
{{ /instagram:profile }}
```

### Manipulate an image

You can manipulate external Instagram image URLs with Glide as usual:

```antlers
{{ instagram:feed }}
    {{ glide src="{thumbnail_url ?? media_url}" format="webp" width="500" }}
        <img src="{{ url }}" width="{{ width }}" height="{{ height }}" alt="...">
    {{ /glide }}
{{ /instagram:feed }}
```

_Since Statamic `v5.43.0`, Glide supports external URLs with query params. Therefore, `v2` of this addon removes the ability to proxy images and drops the `ig_proxy` modifier._ 


### Refreshing Tokens

This only applies to Instagram login. Unified login tokens never expire and are never refreshed.

A long-lived access token is valid for two months.
This would mean that it has to be refreshed manually before it expires. 
When using the `{{ instagram:feed }}`, `{{ instagram:media }}` and `{{ instagram:profile }}` tags,
this is handled automatically for you. 

If you use the API exclusively in PHP, please make sure that you update the token yourself via the scheduler:
```php
Schedule::call(fn() => app(InstagramAPI::class)->refreshAccessToken())->weekly();
```

Please note that the frequency has to be between one day and the mentioned two months until the token expires.


### Child Media (Album)

By default, the addon does not fetch child media for e.g. Albums since that requires additional requests to the API.
Therefore `children` is `null`.
In case you need child media, you can enable `include_child_posts` in the addon config. 

### Using the API in PHP

There is a dedicated `InstagramAPI` class to interact with the API. Its public methods are:

- `cacheKey(...$parts): string`
- `profile(): Profile`
- `feed(): Collection`
- `getAccount(): Account`
- `getLimit(): int`
- `setLimit(int $limit): static`
- `getUserId(): string`
- `getHandle(): string`
- `setHandle(string $handle): static`
- `media(int $id): ?Media`
- `refreshAccessToken(): bool`

For example, this is how you fetch the feed. This will return you a Collection of `Media` objects.

```php
 return app(\MarcoRieser\StatamicInstagram\InstagramAPI::class)
    ->setLimit(12)
    ->setHandle('rickastley')
    ->feed();
```

## License

Statamic Instagram Business API is paid software with an open-source codebase. 
If you want to use it, you’ll need to buy a license from the Statamic Marketplace. 
The license is valid for only one project. Statamic itself is commercial software and has its own license.
