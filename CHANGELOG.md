# Release Notes

## [Unreleased](https://github.com/laravel/cashier-paddle/compare/v1.0.0-beta.2...master)


## [v1.0.0-beta.2 (2020-07-21)](https://github.com/laravel/cashier-paddle/compare/v1.0.0-beta...v1.0.0-beta.2)

### Changed
- Use primary key as customer ID ([#3](https://github.com/laravel/cashier-paddle/pull/3))
- Update minimum Laravel version ([5a0531e](https://github.com/laravel/cashier-paddle/commit/5a0531e8f595b080eed340290c4dd9dc9492d4ce))
- **Refactor to Customer model** ([#18](https://github.com/laravel/cashier-paddle/pull/18), [f59334b](https://github.com/laravel/cashier-paddle/commit/f59334b1b8cc34bf2b70b1ecb7f8fae3789f160c))
- Implement new prices ([#24](https://github.com/laravel/cashier-paddle/pull/24))
- Use endpoint for card info ([#25](https://github.com/laravel/cashier-paddle/pull/25), [3b57ced](https://github.com/laravel/cashier-paddle/commit/3b57ced575be3adcc77e0449540f8cb87f0f3559), [442d4ca](https://github.com/laravel/cashier-paddle/commit/442d4caad31f9a9a04266e86def0af13e1743633))
- Allow to create empty customer ([#26](https://github.com/laravel/cashier-paddle/pull/26))
- **Refactor to polymorphic subscriptions** ([#29](https://github.com/laravel/cashier-paddle/pull/29))
- **Remove paddle_id and paddle_email columns** ([#38](https://github.com/laravel/cashier-paddle/pull/38))
- **Refactor transactions to receipts** ([#40](https://github.com/laravel/cashier-paddle/pull/40))

### Fixed
- Prevent swapping during trial ([#19](https://github.com/laravel/cashier-paddle/pull/19))
- Reset pause state after unpausing ([7d80186](https://github.com/laravel/cashier-paddle/commit/7d80186450b440d453cb5da2bae4afba292e0190))
- Handle Paddle not charging immediately when trial_days=0 ([#35](https://github.com/laravel/cashier-paddle/pull/35))
- Prevent updating quantities during trial ([fcc8b35](https://github.com/laravel/cashier-paddle/commit/fcc8b35485b48d3a392c1e70c368502e69a12d4b))
- Prevent cancelling during grace period ([5016fea](https://github.com/laravel/cashier-paddle/commit/5016fea882bec694101aa07bba2be57ca534a794))


## v1.0.0-beta (2020-06-05)

Initial release.
