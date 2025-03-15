# Changelog

All notable changes to `laravel-vat-eu-validator` will be documented in this file

## Laravel 12 support - 2025-03-15

Thanks to @laravel-shift, we now have compatibility with Laravel 12! 🚀
A big shoutout to @mbardelmeijer, @it-can, and @chellmann for the push to get this update out! 😊

## Add CH vat Validator - 2025-02-26

Add CH vat Validator thx to @jeroen-marinus

## Improved Validator Extension - 2025-01-28

**Improved Validator Extension**: Refactored the `Validator::extend()` and `Validator::replacer()` methods to enhance the flexibility and maintainability of custom validation rules. These changes ensure better integration and easier customization when extending validation logic.

A heartfelt thank you to [frknakk](https://github.com/frknakk) and [vazaha-nl](https://github.com/vazaha-nl) for their contributions and support in improving this package!

## Fix php8.4 warnings - 2025-01-05

thx to @it-can

## Add Hungarian VAT number validation - 2024-11-13

- Implement checksum validation for Hungarian VAT numbers.
- Update `VatValidator.php` to include Hungarian VAT validation logic.
- Add tests for Hungarian VAT number validation in `VatValidatorTest.php`.

Thx to @bsh

## Language files have been introduced - 2024-09-16

Thanks to @dualklip, language files have been introduced.

## Error messages fixes - 2024-07-02

Fix Swap error messages Exist and Format. Thx to @roerlemans

## L11 Support - 2024-06-18

I refactor the code during a MeetUp in Torino. Now I'm supporting L10 and L11 with php 8.2.

## 1.0.0 - 201X-XX-XX

- initial release
