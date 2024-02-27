# Release Notes

## [Unreleased](https://github.com/laravel/cashier-paddle/compare/v2.4.0...2.x)

## [v2.4.0](https://github.com/laravel/cashier-paddle/compare/v2.3.1...v2.4.0) - 2024-02-27

* [2.x] Add the ability to get the paddle error by [@bensherred](https://github.com/bensherred) in https://github.com/laravel/cashier-paddle/pull/248

## [v2.3.1](https://github.com/laravel/cashier-paddle/compare/v2.3.0...v2.3.1) - 2024-02-13

Version

## [v2.3.0](https://github.com/laravel/cashier-paddle/compare/v2.2.1...v2.3.0) - 2024-02-13

* Add support for Client Side Tokens by [@HelgeSverre](https://github.com/HelgeSverre) in https://github.com/laravel/cashier-paddle/pull/245

## [v2.2.1](https://github.com/laravel/cashier-paddle/compare/v2.2.0...v2.2.1) - 2024-01-30

* [2.x] Carbon v3 support by [@driesvints](https://github.com/driesvints) in https://github.com/laravel/cashier-paddle/pull/243

## [v2.2.0](https://github.com/laravel/cashier-paddle/compare/v2.1.0...v2.2.0) - 2024-01-16

* [2.x] Laravel v11 support by [@nunomaduro](https://github.com/nunomaduro) in https://github.com/laravel/cashier-paddle/pull/237

## [v2.1.0](https://github.com/laravel/cashier-paddle/compare/v2.0.7...v2.1.0) - 2024-01-12

* [2.x] Use proper API Key naming by [@driesvints](https://github.com/driesvints) in https://github.com/laravel/cashier-paddle/pull/233
* [2.x] Protect against duplication of customer by [@driesvints](https://github.com/driesvints) in https://github.com/laravel/cashier-paddle/pull/234

## [v2.0.7](https://github.com/laravel/cashier-paddle/compare/v2.0.6...v2.0.7) - 2024-01-09

* [2.x] Fix next payment amount by [@driesvints](https://github.com/driesvints) in https://github.com/laravel/cashier-paddle/pull/231

## [v2.0.6](https://github.com/laravel/cashier-paddle/compare/v2.0.5...v2.0.6) - 2023-12-21

* [2.x] Fix quantity method by [@driesvints](https://github.com/driesvints) in https://github.com/laravel/cashier-paddle/pull/226

## [v2.0.5](https://github.com/laravel/cashier-paddle/compare/v2.0.4...v2.0.5) - 2023-12-19

* [2.x] Clear generic trial for subscription creation by [@driesvints](https://github.com/driesvints) in https://github.com/laravel/cashier-paddle/pull/222

## [v2.0.4](https://github.com/laravel/cashier-paddle/compare/v2.0.3...v2.0.4) - 2023-12-12

* [2.x] Update Cashier::api error message by [@Ahmad-Alkaf](https://github.com/Ahmad-Alkaf) in https://github.com/laravel/cashier-paddle/pull/217
* [2.x] Fix existing customers in Paddle issue by [@driesvints](https://github.com/driesvints) in https://github.com/laravel/cashier-paddle/pull/220

## [v2.0.3](https://github.com/laravel/cashier-paddle/compare/v2.0.2...v2.0.3) - 2023-12-07

* Fix Verification Webhook Signature by [@majdiyassin20](https://github.com/majdiyassin20) in https://github.com/laravel/cashier-paddle/pull/216

## [v2.0.2](https://github.com/laravel/cashier-paddle/compare/v2.0.1...v2.0.2) - 2023-12-07

* Update VerifyWebhookSignature.php by [@majdiyassin20](https://github.com/majdiyassin20) in https://github.com/laravel/cashier-paddle/pull/214
* [2.x] Fix generic trial for customers by [@driesvints](https://github.com/driesvints) in https://github.com/laravel/cashier-paddle/pull/215

## [v2.0.1](https://github.com/laravel/cashier-paddle/compare/v2.0.0...v2.0.1) - 2023-12-05

* Remove loading of migrations by [@driesvints](https://github.com/driesvints) in https://github.com/laravel/cashier-paddle/pull/210

## [v2.0.0](https://github.com/laravel/cashier-paddle/compare/v1.9.2...v2.0.0) - 2023-12-04

* Paddle Billing support

## [v1.9.2](https://github.com/laravel/cashier-paddle/compare/v1.9.1...v1.9.2) - 2023-10-17

- Fix Livewire support by [@ralphjsmit](https://github.com/ralphjsmit) in https://github.com/laravel/cashier-paddle/pull/199

## [v1.9.1](https://github.com/laravel/cashier-paddle/compare/v1.9.0...v1.9.1) - 2023-08-24

- Handle duplicated subscription created event by [@AcTiv3MineD](https://github.com/AcTiv3MineD) in https://github.com/laravel/cashier-paddle/pull/194

## [v1.9.0](https://github.com/laravel/cashier-paddle/compare/v1.8.2...v1.9.0) - 2023-05-22

- Drop PHP 7.2 & 7.3 and Laravel 7 by @driesvints in https://github.com/laravel/cashier-paddle/pull/188

## [v1.8.2](https://github.com/laravel/cashier-paddle/compare/v1.8.1...v1.8.2) - 2023-04-27

- Fix Japanese Yen amounts by @driesvints in https://github.com/laravel/cashier-paddle/pull/184
- Fix Korean Won amounts by @patrickomeara in https://github.com/laravel/cashier-paddle/pull/185
- Move the logic into a Cashier static method by @patrickomeara in https://github.com/laravel/cashier-paddle/pull/186
- Handle the paddle decimal amount by @patrickomeara in https://github.com/laravel/cashier-paddle/pull/187

## [v1.8.1](https://github.com/laravel/cashier-paddle/compare/v1.8.0...v1.8.1) - 2023-01-19

### Fixed

- Modify return type of updateUrl and cancelUrl on Subscription by @LasseRafn in https://github.com/laravel/cashier-paddle/pull/179

## [v1.8.0](https://github.com/laravel/cashier-paddle/compare/v1.7.0...v1.8.0) - 2023-01-06

### Added

- Laravel v10 Support by @driesvints in https://github.com/laravel/cashier-paddle/pull/178

## [v1.7.0](https://github.com/laravel/cashier-paddle/compare/v1.6.2...v1.7.0) - 2023-01-03

### Changed

- Uses PHP Native Type Declarations 🐘  by @nunomaduro in https://github.com/laravel/cashier-paddle/pull/171

## [v1.6.2](https://github.com/laravel/cashier-paddle/compare/v1.6.1...v1.6.2) - 2022-11-15

### Changed

- Do not allow updating quantities to zero by @driesvints in https://github.com/laravel/cashier-paddle/pull/174

## [v1.6.1](https://github.com/laravel/cashier-paddle/compare/v1.6.0...v1.6.1) - 2022-06-22

### Changed

- Replace heavy `symfony/intl` dependency by @jbrooksuk in https://github.com/laravel/cashier-paddle/pull/167

## [v1.6.0](https://github.com/laravel/cashier-paddle/compare/v1.5.5...v1.6.0) - 2022-05-17

### Added

- Add trial expired methods by @driesvints in https://github.com/laravel/cashier-paddle/pull/166

## [v1.5.5](https://github.com/laravel/cashier-paddle/compare/v1.5.4...v1.5.5) - 2022-04-22

### Changed

- Add options to formatAmount by @driesvints in https://github.com/laravel/cashier-paddle/pull/162

## [v1.5.4](https://github.com/laravel/cashier-paddle/compare/v1.5.3...v1.5.4) - 2022-04-01

### Changed

- Pass locale to custom format amount by @driesvints in https://github.com/laravel/cashier-paddle/pull/160

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
