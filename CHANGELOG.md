<!--- BEGIN HEADER -->
# Changelog

All notable changes to this project will be documented in this file.
<!--- END HEADER -->

## [4.0.0-alpha.6](https://gitlab.com/tms-elte/backend-core/compare/v4.0.0-alpha.5...v4.0.0-alpha.6) (2024-12-12)

### ⚠ BREAKING CHANGES

* Fix typo in Notification::dismissible name ([357a73](https://gitlab.com/tms-elte/backend-core/commit/357a73e097a1e199deeca573b37370d6c015a2dc))

### Features

* Added notification scopes ([84fd3e](https://gitlab.com/tms-elte/backend-core/commit/84fd3e3a44f00156e6b617b335100cda8754d078))
* Entry level password for tasks ([aeedc7](https://gitlab.com/tms-elte/backend-core/commit/aeedc7d12c8ec56e46de7f6815655843e9d62a38))
* Late submissions should bypass upload count restrictions ([11d1e7](https://gitlab.com/tms-elte/backend-core/commit/11d1e7555c7302c3777294fce4de5eb929565ada))
* More extensive IP address logging for exam type assignments ([c92280](https://gitlab.com/tms-elte/backend-core/commit/c92280b91fe528805a68375600467d7eb06b4f47))
* Partial Canvas synchronization ([a941d7](https://gitlab.com/tms-elte/backend-core/commit/a941d7cebc3a5701fdc4b204f555d6ce3feae630))

### Bug Fixes

* Block adding students to groups multiple times ([ff3a9b](https://gitlab.com/tms-elte/backend-core/commit/ff3a9bbcdcb77244eb63a046117a062a34665183))
* Test OS should not be configured for new tasks ([9baa7b](https://gitlab.com/tms-elte/backend-core/commit/9baa7bdced4d58dd4f0ec8410643dddcb8157b2e))


---

## [4.0.0-alpha.5](https://gitlab.com/tms-elte/backend-core/compare/v4.0.0-alpha.4...v4.0.0-alpha.5) (2024-11-19)

### Features

* Configurable university code formatting validation ([ab3a0b](https://gitlab.com/tms-elte/backend-core/commit/ab3a0bc53a4f09b9ce1fb217f3efff5500cc4144))
* Restrictable number of submission attempts ([5686f1](https://gitlab.com/tms-elte/backend-core/commit/5686f1483d56066a3d968625c181d8ad434ffc7b))

### Bug Fixes

* Instructor digest notification email sending issue, introduced by Neptun renaming. ([593651](https://gitlab.com/tms-elte/backend-core/commit/5936513bb4c9c6169b392a1844ec76ac82c2e49d))


---

## [4.0.0-alpha.4](https://gitlab.com/tms-elte/backend-core/compare/v4.0.0-alpha.3...v4.0.0-alpha.4) (2024-11-10)

### Features

* Mandatory lecturer assignment for course creation ([d914ec](https://gitlab.com/tms-elte/backend-core/commit/d914ecd81a3ef9869b73a7937f0f03bb83e072cc))

### Bug Fixes

* Reset static analysis status for submissions upon new upload ([04317a](https://gitlab.com/tms-elte/backend-core/commit/04317abf47ea7feacb00e16b8d53ca20cd3ba62f))


---

## [4.0.0-alpha.3](https://gitlab.com/tms-elte/backend-core/compare/v4.0.0-alpha.2...v4.0.0-alpha.3) (2024-10-29)

### ⚠ BREAKING CHANGES

* Rename all Neptun code occurrences to User code ([0bdfeb](https://gitlab.com/tms-elte/backend-core/commit/0bdfebb23112439c181923520912dafcede1350d))
* Rename task related entities ([bbeb3b](https://gitlab.com/tms-elte/backend-core/commit/bbeb3b935a25bdb684660b9443ff694f4243a203))

### Features

* Add maxWebAppRunTime to PrivateSystemInfo ([78b9a0](https://gitlab.com/tms-elte/backend-core/commit/78b9a0fb9f5b719eae69cc977546823e13ddfae7))
* Extend Windows-based .NET evaluator templates with optional architectural analysis ([94d541](https://gitlab.com/tms-elte/backend-core/commit/94d541d4dc00ed74c8bb9175d67464a5dc309d01))


---

## [4.0.0-alpha.2](https://gitlab.com/tms-elte/backend-core/compare/v4.0.0-alpha.1...v4.0.0-alpha.2) (2024-10-08)

### ⚠ BREAKING CHANGES

* Separate StatisticsResource into 2 resources ([71f306](https://gitlab.com/tms-elte/backend-core/commit/71f306ec33768ca6b57ba907826887ec3ae1f7c5))

### Features

* Upgrading gcc/g++ based evaluator templates to Ubuntu 24.04 ([d8def7](https://gitlab.com/tms-elte/backend-core/commit/d8def7520388b7f7f6d8e7954402121d9c237ec0))
* Upgrading MAUI-based evaluator templates to .NET 8. ([76def5](https://gitlab.com/tms-elte/backend-core/commit/76def5b60d85b1469efbd45d0bdd76e8e7206c95))

### Bug Fixes

* Add validation rules for course code fields ([d6cdc5](https://gitlab.com/tms-elte/backend-core/commit/d6cdc51053d76bb86b47957daefee4f8ac789a97))
* Improve Neptun code format validation ([9d4d88](https://gitlab.com/tms-elte/backend-core/commit/9d4d8854c85adba8cfa79e28f536ee350664cf05))


---

## [4.0.0-alpha.1](https://gitlab.com/tms-elte/backend-core/compare/v3.4.0...v4.0.0-alpha.1) (2024-06-20)

### ⚠ BREAKING CHANGES

* Support multiple course codes ([5048e4](https://gitlab.com/tms-elte/backend-core/commit/5048e454d7b9358985329e073ec61793a505ae7b))

### Features

* Add server time to private system response ([8f2a72](https://gitlab.com/tms-elte/backend-core/commit/8f2a723ef6bdb3835bfcda41696cc369678b5849))
* Include IP addresses into Excel exports for tasks ([4c0109](https://gitlab.com/tms-elte/backend-core/commit/4c01095703a5e69c08442179e611c2616b18f859))
* Updated git-php version to 4.2.0 ([077027](https://gitlab.com/tms-elte/backend-core/commit/077027f6458e011582223d2c18ebe063f502688d))

### Bug Fixes

* Exam questions should only have 1 correct answer ([76e96d](https://gitlab.com/tms-elte/backend-core/commit/76e96d55c123069f225e0f4a0f0a282fba43e428))
* Incorrect git repository path for non-submitted solutions ([6eed17](https://gitlab.com/tms-elte/backend-core/commit/6eed1771234804cc289503f42f149776093b0940))
* Instructor files can not be added to regular tasks in canvas synced courses ([f0b26d](https://gitlab.com/tms-elte/backend-core/commit/f0b26dc67711b94b6b9433fa25453874ace3711d))


---

## [3.4.0](https://gitlab.com/tms-elte/backend-core/compare/v3.3.1...v3.4.0) (2024-04-11)

### Features

* Added containerization support ([951d1f](https://gitlab.com/tms-elte/backend-core/commit/951d1fadc7e67f7b9d98fc52a2442d4394fd316b))
* Place plagiarism intermediate data into the temp directory ([172239](https://gitlab.com/tms-elte/backend-core/commit/1722396e36c92049216c116e9ad08ae10559901b))
* Regular task for Canvas synchronized groups ([2ef848](https://gitlab.com/tms-elte/backend-core/commit/2ef84885ba9f9bdc546fdc4b1739e8aba815430f))
* Store IP log for user submissions ([2733f9](https://gitlab.com/tms-elte/backend-core/commit/2733f9e2ff21a5d5e1e25f6018248f3e7ce26952))
* Student initiated Canvas synchronization ([1041dc](https://gitlab.com/tms-elte/backend-core/commit/1041dc2e3f4eba4016e0594c0c2f4c867f517172))


---

## [3.3.1](https://gitlab.com/tms-elte/backend-core/compare/v3.3.0...v3.3.1) (2024-03-03)

### Bug Fixes

* Handle external data directory properly for version controlled tasks ([5f5056](https://gitlab.com/tms-elte/backend-core/commit/5f5056d58e6bdb11a5a18d10cd3f14a0865087da))


---

## [3.3.0](https://gitlab.com/tms-elte/backend-core/compare/v3.2.0...v3.3.0) (2024-02-13)

### Features

* Add User search endpoint ([84ecb3](https://gitlab.com/tms-elte/backend-core/commit/84ecb3c1225ab27b1e41b3af603dae60c3f634d9))
* Upgrading .NET-based evaluator templates to .NET 8. ([02807a](https://gitlab.com/tms-elte/backend-core/commit/02807a0d3ba0c99e1f9d8faca09fdb3f361ac867))


---

## [3.2.0](https://gitlab.com/tms-elte/backend-core/compare/v3.1.0...v3.2.0) (2024-02-05)

### Features

* Increased max length of course code to 30 ([e6884d](https://gitlab.com/tms-elte/backend-core/commit/e6884db64db6570c9b41d75b92f3666d55f91e43))
* Notification management on the backend ([367e70](https://gitlab.com/tms-elte/backend-core/commit/367e704af927533cc4e81c9c584e816d375ce7a3))

### Bug Fixes

* Treat static analysis summary comments in Canvas as auto-generated comments ([325133](https://gitlab.com/tms-elte/backend-core/commit/32513398349d27e4bf59be7edfc9f2151d80d601))


---

## [3.1.0](https://gitlab.com/tms-elte/backend-core/compare/v3.0.2...v3.1.0) (2024-01-03)

### Features

* Added automated testing for web applications ([020986](https://gitlab.com/tms-elte/backend-core/commit/020986029cc9ad44420e8397ad1ffa7bb2944f1c))
* Customizable temp folder with the configuration file ([c0c63a](https://gitlab.com/tms-elte/backend-core/commit/c0c63a04f4aa6a378bf408d87ffd81f7c89ca6fc))

### Bug Fixes

* Clean up plagiarism results upon delete ([0ba6c5](https://gitlab.com/tms-elte/backend-core/commit/0ba6c5e61d8a740a8e164bf9f021d57af1a95271))
* Remove leftover files of possibly failed previous plagiarism checks ([0c12ea](https://gitlab.com/tms-elte/backend-core/commit/0c12ea84cd5cf153c79c6c8fdd3919d3a6357d0f))


---

## [3.0.2](https://gitlab.com/tms-elte/backend-core/compare/v3.0.1...v3.0.2) (2023-10-23)

### Bug Fixes

* Build C# projects before static analysis ([fd159b](https://gitlab.com/tms-elte/backend-core/commit/fd159bd007e3852ef3ced66d6ae5887e1ee84844))


---

## [3.0.1](https://gitlab.com/tms-elte/backend-core/compare/v3.0.0...v3.0.1) (2023-10-07)

### Bug Fixes

* Create new blank submission for each student of a group when synchronizing a new task from Canvas to TMS ([aa142b](https://gitlab.com/tms-elte/backend-core/commit/aa142b2cfbda3afb59755c1b0ce1b8ac1da2a316))


---

## [3.0.0](https://gitlab.com/tms-elte/backend-core/compare/v2.11.2...v3.0.0) (2023-09-26)

### ⚠ BREAKING CHANGES

* Integrate JPlag plagiarism detection tool ([a753a4](https://gitlab.com/tms-elte/backend-core/commit/a753a4123a1436b71198dba93cf945119e55acf8))
* Restructure user resources ([9c3bf9](https://gitlab.com/tms-elte/backend-core/commit/9c3bf9ee5e47dfbaf81586544093766eb7566182))
* Separate evaluator settings from auto tester settings ([aca337](https://gitlab.com/tms-elte/backend-core/commit/aca33702abb54b0a359550b218e4dda4b5711ab0))

### Features

* Added sample seeding options command for developers ([327b83](https://gitlab.com/tms-elte/backend-core/commit/327b83132173db6d12dc11d1b09166f84d2149bb))
* Add record for non-submitted tasks ([5df40a](https://gitlab.com/tms-elte/backend-core/commit/5df40aff8636356b9f3a461bfa7d5154335530fb))
* CodeChecker integration ([adfbc2](https://gitlab.com/tms-elte/backend-core/commit/adfbc2165a66dde26e0db55bc009fea89e19c2f9))
* Execute and store result for all test cases regardless if one fails. ([da413d](https://gitlab.com/tms-elte/backend-core/commit/da413d0330ef4fb2d1fc4c77fb9b1851aa05b538))
* Handle corrupted Canvas submissions ([b59d77](https://gitlab.com/tms-elte/backend-core/commit/b59d77ce59289cf05b2689d949a94df52bdc393b))
* Store Canvas synchronization errors and notify users through email ([ee6415](https://gitlab.com/tms-elte/backend-core/commit/ee641543593db00d23b8e66de7b36a0e08721707))

### Bug Fixes

* Add ellipsis to overflowing task names during Canvas synchronization ([838c4d](https://gitlab.com/tms-elte/backend-core/commit/838c4dc9d3db85d2db124446121cf4cfc47e6984))
* Auto tester error message is always included in the email notification ([065392](https://gitlab.com/tms-elte/backend-core/commit/0653923b37991c46c981a2129d0e2eb8b40904a2))
* Handle file download from Windows containers with Hyper-V isolation ([8097db](https://gitlab.com/tms-elte/backend-core/commit/8097db41bc06ce2ca906ae2f704635a2b4ed4800))
* Handle non-submitted tasks with Canvas integration ([1e7171](https://gitlab.com/tms-elte/backend-core/commit/1e7171751f367d2fb89060ec4b948e4fe839eff2))
* Handle slower container start on Windows with hyperv isolation after tar file upload ([6127f2](https://gitlab.com/tms-elte/backend-core/commit/6127f25548252a328a491de9b25d3cbc50458107))
* Handle slower container start on Windows with hyperv isolation mode ([ea4115](https://gitlab.com/tms-elte/backend-core/commit/ea4115cebd7d256905d2ad44e90e27b6e682a876))
* Ignore file contents for MIME type ([e65ab4](https://gitlab.com/tms-elte/backend-core/commit/e65ab413188898a50471a293cd42f71c93920133))
* Improve saving all related error output with Windows-bases testing ([90de30](https://gitlab.com/tms-elte/backend-core/commit/90de30b3da7ca628b6863d6185e1d287f5f535bb))
* Keep non-English characters in the filenames of CodeChecker HTML reports ([a21e42](https://gitlab.com/tms-elte/backend-core/commit/a21e42927bcb5d840bd510522cb9129e73c08925))
* Pass test case number in Windows-based automated testing ([b549ff](https://gitlab.com/tms-elte/backend-core/commit/b549ff988e3d77be8451549d9f826880b83dff63))
* Properly delete everything from the Git repositories before extracting the new web upload. ([c2cdae](https://gitlab.com/tms-elte/backend-core/commit/c2cdae59aa6334f5460b5cffae7ecce990c224da))
* Remove no submission records upon removing student from group ([12e981](https://gitlab.com/tms-elte/backend-core/commit/12e981c327594858c6b46e4a757870d32ed44b97))
* Restart Windows containers with Hyper-V isolation correctly during testing ([36384c](https://gitlab.com/tms-elte/backend-core/commit/36384c744089062b83261ccbb9b2337743020e5c))
* Unable to upload files to Windows-based Docker container with Hyper-V isolation ([2fc3ee](https://gitlab.com/tms-elte/backend-core/commit/2fc3ee3de7eb9dcb7832df81c81a4a266764685c))
* Windows-based auto testing does not get executed ([7b9168](https://gitlab.com/tms-elte/backend-core/commit/7b9168f65cbbfa423f0c45dcfa97a60a1325aa48))


---

## [3.0.0-beta.1](https://gitlab.com/tms-elte/backend-core/compare/v2.11.2...v3.0.0-beta.1) (2023-09-17)

### ⚠ BREAKING CHANGES

* Integrate JPlag plagiarism detection tool ([a753a4](https://gitlab.com/tms-elte/backend-core/commit/a753a4123a1436b71198dba93cf945119e55acf8))
* Restructure user resources ([9c3bf9](https://gitlab.com/tms-elte/backend-core/commit/9c3bf9ee5e47dfbaf81586544093766eb7566182))
* Separate evaluator settings from auto tester settings ([aca337](https://gitlab.com/tms-elte/backend-core/commit/aca33702abb54b0a359550b218e4dda4b5711ab0))

### Features

* Added sample seeding options command for developers ([327b83](https://gitlab.com/tms-elte/backend-core/commit/327b83132173db6d12dc11d1b09166f84d2149bb))
* Add record for non-submitted tasks ([5df40a](https://gitlab.com/tms-elte/backend-core/commit/5df40aff8636356b9f3a461bfa7d5154335530fb))
* CodeChecker integration ([adfbc2](https://gitlab.com/tms-elte/backend-core/commit/adfbc2165a66dde26e0db55bc009fea89e19c2f9))
* Execute and store result for all test cases regardless if one fails ([da413d](https://gitlab.com/tms-elte/backend-core/commit/da413d0330ef4fb2d1fc4c77fb9b1851aa05b538))
* Handle corrupted Canvas submissions ([b59d77](https://gitlab.com/tms-elte/backend-core/commit/b59d77ce59289cf05b2689d949a94df52bdc393b))
* Store Canvas synchronization errors and notify users through email ([ee6415](https://gitlab.com/tms-elte/backend-core/commit/ee641543593db00d23b8e66de7b36a0e08721707))

### Bug Fixes

* Ignore file contents for MIME type when serving plagiarism results ([e65ab4](https://gitlab.com/tms-elte/backend-core/commit/e65ab413188898a50471a293cd42f71c93920133))
* Add ellipsis to overflowing task names during Canvas synchronization ([838c4d](https://gitlab.com/tms-elte/backend-core/commit/838c4dc9d3db85d2db124446121cf4cfc47e6984))
* Auto tester error message is always included in the email notification ([065392](https://gitlab.com/tms-elte/backend-core/commit/0653923b37991c46c981a2129d0e2eb8b40904a2))
* Handle file download from Windows containers with Hyper-V isolation ([8097db](https://gitlab.com/tms-elte/backend-core/commit/8097db41bc06ce2ca906ae2f704635a2b4ed4800))
* Handle slower container start on Windows with hyperv isolation after tar file upload ([6127f2](https://gitlab.com/tms-elte/backend-core/commit/6127f25548252a328a491de9b25d3cbc50458107))
* Handle slower container start on Windows with hyperv isolation mode ([ea4115](https://gitlab.com/tms-elte/backend-core/commit/ea4115cebd7d256905d2ad44e90e27b6e682a876))
* Improve saving all related error output with Windows-bases testing ([90de30](https://gitlab.com/tms-elte/backend-core/commit/90de30b3da7ca628b6863d6185e1d287f5f535bb))
* Keep non-English characters in the filenames of CodeChecker HTML reports ([a21e42](https://gitlab.com/tms-elte/backend-core/commit/a21e42927bcb5d840bd510522cb9129e73c08925))
* Pass test case number in Windows-based automated testing ([b549ff](https://gitlab.com/tms-elte/backend-core/commit/b549ff988e3d77be8451549d9f826880b83dff63))
* Properly delete everything from the Git repositories before extracting the new web upload ([c2cdae](https://gitlab.com/tms-elte/backend-core/commit/c2cdae59aa6334f5460b5cffae7ecce990c224da))
* Restart Windows containers with Hyper-V isolation correctly during testing ([36384c](https://gitlab.com/tms-elte/backend-core/commit/36384c744089062b83261ccbb9b2337743020e5c))
* Unable to upload files to Windows-based Docker container with Hyper-V isolation ([2fc3ee](https://gitlab.com/tms-elte/backend-core/commit/2fc3ee3de7eb9dcb7832df81c81a4a266764685c))
* Windows-based auto testing does not get executed ([7b9168](https://gitlab.com/tms-elte/backend-core/commit/7b9168f65cbbfa423f0c45dcfa97a60a1325aa48))


---

## [3.0.0-alpha.8](https://gitlab.com/tms-elte/backend-core/compare/v3.0.0-alpha.7...v3.0.0-alpha.8) (2023-06-11)

### Bug Fixes

* Improve saving all related error output with Windows-bases testing ([90de30](https://gitlab.com/tms-elte/backend-core/commit/90de30b3da7ca628b6863d6185e1d287f5f535bb))


---

## [3.0.0-alpha.7](https://gitlab.com/tms-elte/backend-core/compare/v3.0.0-alpha.6...v3.0.0-alpha.7) (2023-06-10)

### Bug Fixes

* Handle file download from Windows containers with Hyper-V isolation ([8097db](https://gitlab.com/tms-elte/backend-core/commit/8097db41bc06ce2ca906ae2f704635a2b4ed4800))


---

## [3.0.0-alpha.6](https://gitlab.com/tms-elte/backend-core/compare/v3.0.0-alpha.5...v3.0.0-alpha.6) (2023-06-10)

### Bug Fixes

* Restart Windows containers with Hyper-V isolation correctly during testing ([36384c](https://gitlab.com/tms-elte/backend-core/commit/36384c744089062b83261ccbb9b2337743020e5c))


---

## [3.0.0-alpha.5](https://gitlab.com/tms-elte/backend-core/compare/v3.0.0-alpha.4...v3.0.0-alpha.5) (2023-06-10)

### Bug Fixes

* Handle slower container start on Windows with hyperv isolation after restart ([6127f2](https://gitlab.com/tms-elte/backend-core/commit/6127f25548252a328a491de9b25d3cbc50458107))
* Handle slower container start on Windows with hyperv isolation after initial start ([ea4115](https://gitlab.com/tms-elte/backend-core/commit/ea4115cebd7d256905d2ad44e90e27b6e682a876))


---

## [3.0.0-alpha.4](https://gitlab.com/tms-elte/backend-core/compare/v3.0.0-alpha.3...v3.0.0-alpha.4) (2023-05-12)

### ⚠ BREAKING CHANGES

* Integrate JPlag plagiarism detection tool ([a753a4](https://gitlab.com/tms-elte/backend-core/commit/a753a4123a1436b71198dba93cf945119e55acf8))

### Features

* Added sample seeding options command for developers ([327b83](https://gitlab.com/tms-elte/backend-core/commit/327b83132173db6d12dc11d1b09166f84d2149bb))

### Bug Fixes

* Properly delete everything from the Git repositories before extracting the new web upload. ([c2cdae](https://gitlab.com/tms-elte/backend-core/commit/c2cdae59aa6334f5460b5cffae7ecce990c224da))
* Unable to upload files to Windows-based Docker container with Hyper-V isolation ([2fc3ee](https://gitlab.com/tms-elte/backend-core/commit/2fc3ee3de7eb9dcb7832df81c81a4a266764685c))


---

## [3.0.0-alpha.3](https://gitlab.com/tms-elte/backend-core/compare/v3.0.0-alpha.2...v3.0.0-alpha.3) (2023-04-28)

### Bug Fixes

* Add ellipsis ('...') to overflowing task names during Canvas synchronization ([838c4d](https://gitlab.com/tms-elte/backend-core/commit/838c4dc9d3db85d2db124446121cf4cfc47e6984))


---

## [3.0.0-alpha.2](https://gitlab.com/tms-elte/backend-core/compare/v3.0.0-alpha.1...v3.0.0-alpha.2) (2023-04-07)

### Bug Fixes

* Keep non-English characters in the filenames of CodeChecker HTML reports ([a21e42](https://gitlab.com/tms-elte/backend-core/commit/a21e42927bcb5d840bd510522cb9129e73c08925))


---

## [3.0.0-alpha.1](https://gitlab.com/tms-elte/backend-core/compare/v2.11.2...v3.0.0-alpha.1) (2023-03-29)

### ⚠ BREAKING CHANGES

* Restructure user resources ([9c3bf9](https://gitlab.com/tms-elte/backend-core/commit/9c3bf9ee5e47dfbaf81586544093766eb7566182))
* Separate evaluator settings from auto tester settings ([aca337](https://gitlab.com/tms-elte/backend-core/commit/aca33702abb54b0a359550b218e4dda4b5711ab0))

### Features

* CodeChecker integration ([adfbc2](https://gitlab.com/tms-elte/backend-core/commit/adfbc2165a66dde26e0db55bc009fea89e19c2f9))

### Bug Fixes

* Auto tester error message is always included in the email notification ([065392](https://gitlab.com/tms-elte/backend-core/commit/0653923b37991c46c981a2129d0e2eb8b40904a2))


---

## [2.11.2](https://gitlab.com/tms-elte/backend-core/compare/v2.11.1...v2.11.2) (2023-03-26)
### Bug Fixes

* Auto tester error message is always included in the email notification ([35be30](https://gitlab.com/tms-elte/backend-core/commit/35be3057f13554f65734a1ea855734203c93d458))


---


## [2.11.1](https://gitlab.com/tms-elte/backend-core/compare/v2.11.0...v2.11.1) (2023-03-22)
### Bug Fixes

* Store standard output if standard error is empty upon compilation failure of student submission ([999922](https://gitlab.com/tms-elte/backend-core/commit/9999225ceb44a5984c9315a707d63933820a3f76))


---

## [2.11.0](https://gitlab.com/tms-elte/backend-core/compare/v2.10.0...v2.11.0) (2023-03-06)
### Features

* Import multiple test cases from csv and xls files ([de01cd](https://gitlab.com/tms-elte/backend-core/commit/de01cda07c38031c27bb34e1e94661edb002b8a6))
* Task-level git repositories ([013185](https://gitlab.com/tms-elte/backend-core/commit/0131856560aa0754220b1e7b028b173cd51cd05e))

### Bug Fixes

* Exception chaining issue of SubmissionRunnerExceptions in automated evaluator initialization ([4ec5d2](https://gitlab.com/tms-elte/backend-core/commit/4ec5d2765478321130ed38a76e664f8b12359e6f))

---

## [2.10.0](https://gitlab.com/tms-elte/backend-core/compare/v2.9.1...v2.10.0) (2023-02-07)
### Features

* API endpoint to display various statistics for admins ([224ed9](https://gitlab.com/tms-elte/backend-core/commit/224ed93b74121e7b328f5eec5ec79d4ee4893693))
* email notifications for personal note edits ([3d65eb](https://gitlab.com/tms-elte/backend-core/commit/3d65eb494c5364b3fc6f831bc1ef0d95f84986a5))
* Personal notes storage ([7b03aa](https://gitlab.com/tms-elte/backend-core/commit/7b03aaa98749a07712a7a8f5a87b10294c48170c))

### Bug Fixes

* Encode user content in emails ([b51c6f](https://gitlab.com/tms-elte/backend-core/commit/b51c6fd087086dc5a800eb521c0129b8d623ce4d))
* Fixed test cases failing on newline mismatch ([fc0a82](https://gitlab.com/tms-elte/backend-core/commit/fc0a8212473c851a433f0787ffdb336d20ab3c05))
* Fix incorrect local links in downloaded MOSS results ([ca1be4](https://gitlab.com/tms-elte/backend-core/commit/ca1be48324d0741b326bc927d65337faf7aa90b4))
* Fix incorrect logging during Canvas synchronization ([ae7fdf](https://gitlab.com/tms-elte/backend-core/commit/ae7fdfcf971f037047afd7addae2d0dd1bafd7f5))
* Handle exceptions during injecting student submission into Docker container as new Initiation Failed status ([96d7df](https://gitlab.com/tms-elte/backend-core/commit/96d7df9f92f256bbac5099fa416e1a94d0b26ba0))
* Retry MOSS result download upon timeout or failure, and add a small amount of wait time after each download ([37eebe](https://gitlab.com/tms-elte/backend-core/commit/37eebecad7aa9379f6ff0a0bdcb2b8fc396cdffe))
* SubmissionRunner tries to copy all test files into the container ([f02d1a](https://gitlab.com/tms-elte/backend-core/commit/f02d1aa4b3f9987a24a446965f78c2851e8e82f5))

---

## [2.9.1](https://gitlab.com/tms-elte/backend-core/compare/v2.9.0...v2.9.1) (2022-11-08)
### Bug Fixes

* Handle too large stdout and stdin sizes in automated tester ([f8991b](https://gitlab.com/tms-elte/backend-core/commit/f8991bb265e62a7a0a8ff1b4e0a49f9ea40144a3))

---

## [2.9.0](https://gitlab.com/tms-elte/backend-core/compare/v2.8.1...v2.9.0) (2022-10-21)
### Features

* Added 'In Progress' for student submissions ([395d8f](https://gitlab.com/tms-elte/backend-core/commit/395d8f420d6c1552be1f983504c94323e29836fa))
* Integrate job scheduler ([eec04d](https://gitlab.com/tms-elte/backend-core/commit/eec04d918573028d6a06b8c1650edf8652d3369d))
* Publish file and POST request size limits ([515ad3](https://gitlab.com/tms-elte/backend-core/commit/515ad37e805445c23dbfa6c401feaa3d1d8d8632))

### Bug Fixes

* Cannot save student file after the first commit for version controlled tasks ([915ad3](https://gitlab.com/tms-elte/backend-core/commit/915ad3749be2e87eb3b4a058fe398269e5bf04bb))
* Fix the callback URL in the post-receive Git hook ([a2cfc9](https://gitlab.com/tms-elte/backend-core/commit/a2cfc910fc3a9a2b0cabbbd14b0e119bb7a237fd))
* Remove possible double slashes from constructed URLs ([320d8d](https://gitlab.com/tms-elte/backend-core/commit/320d8d81007f0a2b2c40901478bb23a446d9fcf2))
* User friendly error message for MOSS server unavailability ([b4e59b](https://gitlab.com/tms-elte/backend-core/commit/b4e59bae36e32737fe58fdd8f836ba81ea4811d8))

---

## [2.8.1](https://gitlab.com/tms-elte/backend-core/compare/v2.8.0...v2.8.1) (2022-10-12)
### Bug Fixes

* Correctly save student files after the first commit for version controlled tasks ([915ad3](https://gitlab.com/tms-elte/backend-core/commit/915ad3749be2e87eb3b4a058fe398269e5bf04bb))
* Fix the callback URL in the post-receive Git hook ([a2cfc9](https://gitlab.com/tms-elte/backend-core/commit/a2cfc910fc3a9a2b0cabbbd14b0e119bb7a237fd))

---

## [2.8.0](https://gitlab.com/tms-elte/backend-core/compare/v2.7.2...v2.8.0) (2022-09-16)
### Features

* Incorporate upload count prioritization in student submission evaluation ([708d1a](https://gitlab.com/tms-elte/backend-core/commit/708d1affe9bd556c50d580203676c9c31768d5fd))
* Support continuous Canvas synchronization with prioritizing groups not synchronized for the longest time ([c0e39d](https://gitlab.com/tms-elte/backend-core/commit/c0e39d5e4ef4b8838f6925a168ef4064908bb262))

### Bug Fixes

* Improve non UTF-8 character removal on Canvas synchronization ([d66ac1](https://gitlab.com/tms-elte/backend-core/commit/d66ac154b2803930562966469c6391dbeb799bba))
* Permission fixes when the evaluator is running on a Windows host machine ([3d1d75](https://gitlab.com/tms-elte/backend-core/commit/3d1d7511887c7d68cc65a7e5b4b337e07232cb58))

---

## [2.7.2](https://gitlab.com/tms-elte/backend-core/compare/v2.7.1...v2.7.2) (2022-06-22)
### Bug Fixes

* Correctly process multiple submissions upon each evaluator/check call ([baad92](https://gitlab.com/tms-elte/backend-core/commit/baad920011c7c1a13af5d934e9f38272949e1729))
* Enforce UTF8 charset restriction on submission comments from Canvas ([05c030](https://gitlab.com/tms-elte/backend-core/commit/05c03063f55223f9c04cd602fd3a7270506af71f))
* Improve non UTF-8 character removal on Canvas synchronization ([03373a](https://gitlab.com/tms-elte/backend-core/commit/03373a54e9937ad6caff0c19fb741dde5d56a572))

---

## [2.7.1](https://gitlab.com/tms-elte/backend-core/compare/v2.7.0...v2.7.1) (2022-05-21)
### Bug Fixes

* Correctly display the sides of the comparison table of the downloaded MOSS results ([e4c24e](https://gitlab.com/tms-elte/backend-core/commit/e4c24e1144e8bdedbf55adc9790e1f175c7d3e3d))
* Display MOSS thermometer icons locally ([600958](https://gitlab.com/tms-elte/backend-core/commit/600958983a95f231d9ef05d2b733a3bf8b32ffa0))

---

## [2.7.0](https://gitlab.com/tms-elte/backend-core/compare/v2.6.1...v2.7.0) (2022-05-16)
### Features

* Add mail digest for due submissions ([5f00bb](https://gitlab.com/tms-elte/backend-core/commit/5f00bbe71a11b1e7d7c35223a2d94bfa698124f6))
* CodeCompass integration ([269036](https://gitlab.com/tms-elte/backend-core/commit/269036b5e3334aef78dc33523f32a935846e738e))
* Enable remote execution for web applications ([2410c6](https://gitlab.com/tms-elte/backend-core/commit/2410c622b97ff45d1956559912349fa3de5a6807))
* List plagiarism basefiles without a semester ([3a08f0](https://gitlab.com/tms-elte/backend-core/commit/3a08f032e42b56df2a4bedc1d48c4fa07e45f550))
* Password protected tasks ([d5858e](https://gitlab.com/tms-elte/backend-core/commit/d5858e91d5624aeab66ed15bdf5d1edd18f26912))
* Publish Canvas url for groups and tasks ([d4f65e](https://gitlab.com/tms-elte/backend-core/commit/d4f65ee3ef34771f6d9dfad9b47775e7a16a4af7))

### Bug Fixes

* Relative paths on windows are not supported for symlinks (version control support) ([5ab405](https://gitlab.com/tms-elte/backend-core/commit/5ab405a83638e0b5af274de70a5dbbcc01610106))

---

## [2.6.1](https://gitlab.com/tms-elte/backend-core/compare/v2.6.0...v2.6.1) (2022-04-16)
### Bug Fixes

* Ensure to append the missing latest tag to the Docker image name in the automated testing configuration ([4ad544](https://gitlab.com/tms-elte/backend-core/commit/4ad544636c6f31aa02f3ce59db7cd3ef0615cbb9))

---

## [2.6.0](https://gitlab.com/tms-elte/backend-core/compare/v2.5.2...v2.6.0) (2022-04-14)
### Features

* Add deletable property to basefile resources ([d6b8a3](https://gitlab.com/tms-elte/backend-core/commit/d6b8a33957e4f2cb2b572558c27c1aa7d8c7b430))
* Added option to reevaluate ungraded submissions upon configuration change of automated testing ([f0eae2](https://gitlab.com/tms-elte/backend-core/commit/f0eae26c79fefe0133b302d352930f76443edef8))
* Store upload count for student submissions ([0f2b75](https://gitlab.com/tms-elte/backend-core/commit/0f2b75cd2644ed789a150c392f6ec9cddcca8c44))

### Bug Fixes

* Fixed daylight savings time being correctly counted towards submission delay ([3e46fc](https://gitlab.com/tms-elte/backend-core/commit/3e46fc2562192311a8170f66ddfae2ed48ee1b39))
* Return only basefiles for the selected tasks ([8b007c](https://gitlab.com/tms-elte/backend-core/commit/8b007c36bdb5d245c208da8adf66b6127635f45f))

---

## [2.5.2](https://gitlab.com/tms-elte/backend-core/compare/v2.5.1...v2.5.2) (2022-04-01)
### Bug Fixes

* Copy only the test files of the selected task into the docker container in the automated tester ([23b098](https://gitlab.com/tms-elte/backend-core/commit/23b098c3d17cbe874c72f6609d2554a8163d9697))

---

## [2.5.1](https://gitlab.com/tms-elte/backend-core/compare/v2.5.0...v2.5.1) (2022-03-30)
### Bug Fixes

* Increase compilation and run instruction length limit in automated evaluator to 65535 characters ([56ffe6](https://gitlab.com/tms-elte/backend-core/commit/56ffe6789ac41973a0829855bde16814a6d161e4))

---

## [2.5.0](https://gitlab.com/tms-elte/backend-core/compare/v2.4.2...v2.5.0) (2022-03-28)
### Features

* Add support for image update from remote docker repository ([bae58f](https://gitlab.com/tms-elte/backend-core/commit/bae58fda3b8be9788c3356ea87b8b3da28780bcf))
* Plagiarism basefiles and results download ([c3010a](https://gitlab.com/tms-elte/backend-core/commit/c3010a07e9a4b46b937343c0871597e16dffc078))

### Bug Fixes

* Always store detailed compile or runtime error messages for the evaluator ([f4fe04](https://gitlab.com/tms-elte/backend-core/commit/f4fe048231c5a012e506f49e133930c71bdc0bf8))
* Remove FL_NOCASE flag when opening ZIP ([527990](https://gitlab.com/tms-elte/backend-core/commit/52799035709f62f6e0ab32e378973d5b9cb9e7ad))
* Return display name with LDAP-based auth properly ([1d702a](https://gitlab.com/tms-elte/backend-core/commit/1d702ad4b0690f11c90b0e56389ba0d52a818c7a))

---

## [2.4.2](https://gitlab.com/tms-elte/backend-core/compare/v2.4.1...v2.4.2) (2022-03-26)
### Bug Fixes

* Allow to delete evaluator test files for Canvas synchronized groups ([f394c1](https://gitlab.com/tms-elte/backend-core/commit/f394c1f213aa6870293436ce4b7a971fefd1c157))

---

## [2.4.1](https://gitlab.com/tms-elte/backend-core/compare/v2.4.0...v2.4.1) (2022-03-23)
### Bug Fixes

* Properly remove stucked containers before recreation ([d9dcec](https://gitlab.com/tms-elte/backend-core/commit/d9dcec37c3d98412f363979e568bce9a171992c9))

---

## [2.4.0](https://gitlab.com/tms-elte/backend-core/compare/v2.3.1...v2.4.0) (2022-03-22)
### Features

* Added OpenAPI documentation and SwaggerUI interface ([9ce962](https://gitlab.com/tms-elte/backend-core/commit/9ce9621d4a581332cb23bc803f605b3cd654c27d))
* File upload support for automatic tester ([a78b55](https://gitlab.com/tms-elte/backend-core/commit/a78b554f8a1dfcdccdb7930d65e8e281899dd782))
* Generate PHP API documentation with phpdoc and publish it to GitLab Pages ([f32790](https://gitlab.com/tms-elte/backend-core/commit/f3279068f508fec0b95657c02be4f1d1f90a201f))
* Publish version information ([8d3e8a](https://gitlab.com/tms-elte/backend-core/commit/8d3e8a0f9b6151a41d95fca4a303a2134b66eedf))

### Bug Fixes

* Avoid possible infinite loops in Canvas synchronization upon HTTP request error ([f9bf11](https://gitlab.com/tms-elte/backend-core/commit/f9bf11654ca38f05a1eeafa58f3b8a56c08c8e50))
* Remove non UTF-8 characters from task name and description upon Canvas synchronization ([473249](https://gitlab.com/tms-elte/backend-core/commit/4732495b732336dc9f2c39b00658393c7cd517ae))
* The setup/seed command is unable to add the initial group ([d1dd91](https://gitlab.com/tms-elte/backend-core/commit/d1dd91dcee7ab20850346abdd7b8c57c8d35f653))

---

## [2.3.1](https://gitlab.com//tms-elte/backend-core/compare/v2.3.0...v2.3.1) (2022-01-15)
### Bug Fixes

* Extend exam deadline by 30 sec to properly accept JS-based auto submissions when the time is up ([6789cb](https://gitlab.com//tms-elte/backend-core/commit/6789cb0b4e2204c7a97dd8b8bec7167925a1d256))
* Extend task name to 40 character length and trim task name when imported from Canvas ([03df5b](https://gitlab.com//tms-elte/backend-core/commit/03df5bec7c69c9d09bb2512ab194d709def3df4d))

---

## [2.3.0](https://gitlab.com//tms-elte/backend-core/compare/v2.2.0...v2.3.0) (2022-01-13)
### Features

* Command line arguments for test cases ([9ff494](https://gitlab.com//tms-elte/backend-core/commit/9ff494fab4286eb49b557ec25ee7543ac49ebbb5))

### Bug Fixes

* Auto tester is automatically turned on after synchronization for each Canvas task ([b8dcd6](https://gitlab.com//tms-elte/backend-core/commit/b8dcd69b136be6a0081b3eb2471136e6382678f1))
* Do not overwrite files with matching names in plagiarism checks ([0c5605](https://gitlab.com//tms-elte/backend-core/commit/0c5605b89e234c3db5ad12734f6b210832c8abf1))
* Replace string-based timestamp comparisons ([72e311](https://gitlab.com//tms-elte/backend-core/commit/72e311758c684311ed63f4d1f2d1494e58ccf270))

---

## [2.2.0](https://gitlab.com//tms-elte/backend-core/compare/v2.1.1...v2.2.0) (2021-11-17)
### Features

* Return future exams ([e0c16b](https://gitlab.com//tms-elte/backend-core/commit/e0c16b9e47eb05fbc22ed96f6c69f434be5084a0))
* Support multi command run instructions in automated testing ([b08dcf](https://gitlab.com//tms-elte/backend-core/commit/b08dcfde2ef72fedbec3ccb602d61314ba52658a))
* User settings backend ([eca45f](https://gitlab.com//tms-elte/backend-core/commit/eca45fefba3a753217321a038f5e35a966712b0c))

### Bug Fixes

* Remove non UTF-8 characters from stdout and stderr output on automated evaluation of submissions ([c7ec8d](https://gitlab.com//tms-elte/backend-core/commit/c7ec8d7a0c20bfccc896fc38e12e5cdb0503a736))

---

## [2.1.1](https://gitlab.com//tms-elte/backend-core/compare/v2.1.0...v2.1.1) (2021-11-09)
### Bug Fixes

* Synchronize Canvas tasks with custom user deadline overrides ([67938b](https://gitlab.com//tms-elte/backend-core/commit/67938beac1227e63de6546f68c7013e380a369d2))

---

## [2.1.0](https://gitlab.com//tms-elte/backend-core/compare/v2.0.0...v2.1.0) (2021-10-27)
### Features

* Add markdown formatting on task descriptions in emails ([824cfd](https://gitlab.com//tms-elte/backend-core/commit/824cfd87a82df2b7f6f05af670508f6d5456c9b7))
* Extend events when logging is performed, modify log prefix to be more informative ([1cc1e2](https://gitlab.com//tms-elte/backend-core/commit/1cc1e2370155a8cf007161446e5fdd775f16280a))
* Use ISO 8601 datetime format and manage timezones ([87e266](https://gitlab.com//tms-elte/backend-core/commit/87e26687b6c109c4a7cb470a98c74b5f9e2eba45))

### Bug Fixes

* Skip to download invalid/corrupted Canvas submissions ([e84da0](https://gitlab.com//tms-elte/backend-core/commit/e84da0282ce30cf64ea7f9f61e63ca6344b42c3c))

---

## [2.0.0](https://gitlab.com//tms-elte/backend-core/compare/83e1f3707c03bd9027cc44a16636bd109b6d5480...v2.0.0) (2021-09-22)

Initial public release.

---
