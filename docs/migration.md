# Migration

## From 1.4 to 1.5

In `composer.json` change `"metabolism/wordpress-bundle": "1.4.*"` to `"metabolism/wordpress-bundle": "1.5.*"` then run `composer update`

After update

- Remove `src/Service/Context.php`
- Update `src/Controller/BlogController.php`, replace `use App\Service\Context` with `use Metabolism\WordpressBundle\Service\ContextService as Context`
- Update `src/Controller/BlogController.php`, replace `frontAction` with `homeAction`
- Update `public/index.php`, replace `$request` with `$httpRequest`