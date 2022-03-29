# Release Notes

## [Unreleased](https://github.com/laravel/cashier-paddle/compare/v1.5.3...1.x)

## [v1.5.3](https://github.com/laravel/cashier-paddle/compare/v1.5.2...v1.5.3) - 2022-03-28

### Fixed

- Fix type for event by @driesvints in https://github.com/laravel/cashier-paddle/pull/159

## [v1.5.2](https://github.com/laravel/cashier-paddle/compare/v1.5.1...v1.5.2) - 2022-03-18

### Changed

- Allow to serialize payment object by @driesvints in https://github.com/laravel/cashier-paddle/pull/157

## [v1.5.1](https://github.com/laravel/cashier-paddle/compare/v1.5.0...v1.5.1) - 2022-02-22

### Changed

- Allow spatie/url 2.0 by @bashgeek in https://github.com/laravel/cashier-paddle/pull/154

## [v1.5.0 (2022-01-12)](https://github.com/laravel/cashier-paddle/compare/v1.4.8...v1.5.0)

### Added

- Add hasTax method ([#149](https://github.com/laravel/cashier-paddle/pull/149))

### Changed

- Laravel 9 Support ([#150](https://github.com/laravel/cashier-paddle/pull/150))

## [v1.4.8 (2021-11-30)](https://github.com/laravel/cashier-paddle/compare/v1.4.7...v1.4.8)

### Fixed

- Fix empty `paymentMethod` ([#148](https://github.com/laravel/cashier-paddle/pull/148))

## [v1.4.7 (2021-11-23)](https://github.com/laravel/cashier-paddle/compare/v1.4.6...v1.4.7)

### Changed

- Allow moneyphp version 4 ([#143](https://github.com/laravel/cashier-paddle/pull/143))

## [v1.4.6 (2021-08-03)](https://github.com/laravel/cashier-paddle/compare/v1.4.5...v1.4.6)

### Fixed

- Update environment sandbox Paddle.js ([#129](https://github.com/laravel/cashier-paddle/pull/129))
- Fix refunds ([#131](https://github.com/laravel/cashier-paddle/pull/131))

## [v1.4.5 (2021-06-08)](https://github.com/laravel/cashier-paddle/compare/v1.4.4...v1.4.5)

### Fixed

- Trim whitespace for pay link values ([#121](https://github.com/laravel/cashier-paddle/pull/121))

## [v1.4.4 (2021-03-09)](https://github.com/laravel/cashier-paddle/compare/v1.4.3...v1.4.4)

### Fixed

- Fix maxlength characters for charge title ([#112](https://github.com/laravel/cashier-paddle/pull/112))

## [v1.4.3 (2021-02-23)](https://github.com/laravel/cashier-paddle/compare/v1.4.2...v1.4.3)

### Changed

- Allow custom models ([#106](https://github.com/laravel/cashier-paddle/pull/106))

## [v1.4.2 (2021-02-19)](https://github.com/laravel/cashier-paddle/compare/v1.4.1...v1.4.2)

### Fixed

- Fix payment method calls ([#105](https://github.com/laravel/cashier-paddle/pull/105))

## [v1.4.1 (2021-01-05)](https://github.com/laravel/cashier-paddle/compare/v1.4.0...v1.4.1)

### Fixed

- Fix bug with `cancelNow` ([#99](https://github.com/laravel/cashier-paddle/pull/99))
- Fix cancelling paused subscriptions ([#101](https://github.com/laravel/cashier-paddle/pull/101), [#102](https://github.com/laravel/cashier-paddle/pull/102))

## [v1.4.0 (2020-12-15)](https://github.com/laravel/cashier-paddle/compare/v1.3.0...v1.4.0)

### Added

- Subscription Modifiers ([#95](https://github.com/laravel/cashier-paddle/pull/95))

## [v1.3.0 (2020-11-03)](https://github.com/laravel/cashier-paddle/compare/v1.2.3...v1.3.0)

### Added

- PHP 8 Support ([#91](https://github.com/laravel/cashier-paddle/pull/91))

## [v1.2.3 (2020-10-20)](https://github.com/laravel/cashier-paddle/compare/v1.2.2...v1.2.3)

### Fixed

- Fix trial ends at ([#87](https://github.com/laravel/cashier-paddle/pull/87))
- Ignore manual paylinks ([#89](https://github.com/laravel/cashier-paddle/pull/89))

## [v1.2.2 (2020-10-06)](https://github.com/laravel/cashier-paddle/compare/v1.2.1...v1.2.2)

### Fixed

- Missing use statement for `SubscriptionPaymentFailed` ([#81](https://github.com/laravel/cashier-paddle/pull/81))
- Fix n+1 problem with subscription retrieval ([#83](https://github.com/laravel/cashier-paddle/pull/83))

## [v1.2.1 (2020-09-29)](https://github.com/laravel/cashier-paddle/compare/v1.2.0...v1.2.1)

### Changed

- Allow customer values in payload override ([#78](https://github.com/laravel/cashier-paddle/pull/78))

## [v1.2.0 (2020-09-22)](https://github.com/laravel/cashier-paddle/compare/v1.1.0...v1.2.0)

### Added

- Event Improvements ([#72](https://github.com/laravel/cashier-paddle/pull/72))

## [v1.1.0 (2020-09-01)](https://github.com/laravel/cashier-paddle/compare/v1.0.0...v1.1.0)

### Added

- Added function to see when the trial is ending ([#69](https://github.com/laravel/cashier-paddle/pull/69))
- Update webhook events to be more expressive ([#67](https://github.com/laravel/cashier-paddle/pull/67))

## [v1.0.0 (2020-08-25)](https://github.com/laravel/cashier-paddle/compare/v1.0.0-beta.2...v1.0.0)

### Added

- Add `paymentMethod` method ([6f78dfe](https://github.com/laravel/cashier-paddle/commit/6f78dfe10a4fcb3033591385a1f20eb16412a8b7))
- Laravel 8 support ([#62](https://github.com/laravel/cashier-paddle/pull/62))

### Changed

- Refactor exception throwing for updates ([#54](https://github.com/laravel/cashier-paddle/pull/54))

### Fixed

- Fix webhook controller quantities ([#44](https://github.com/laravel/cashier-paddle/pull/44))
- Fix receipt relation with subscription ([#49](https://github.com/laravel/cashier-paddle/pull/49))

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
