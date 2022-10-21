<!--- BEGIN HEADER -->
# Changelog

All notable changes to this project will be documented in this file.
<!--- END HEADER -->

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

* Relative paths on windows are not supported for symlinks (version control support). ([5ab405](https://gitlab.com/tms-elte/backend-core/commit/5ab405a83638e0b5af274de70a5dbbcc01610106))

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

* Copy only the test files of the selected task into the docker container in the automated tester. ([23b098](https://gitlab.com/tms-elte/backend-core/commit/23b098c3d17cbe874c72f6609d2554a8163d9697))

---

## [2.5.1](https://gitlab.com/tms-elte/backend-core/compare/v2.5.0...v2.5.1) (2022-03-30)
### Bug Fixes

* Increase compilation and run instruction length limit in automated evaluator to 65535 characters. ([56ffe6](https://gitlab.com/tms-elte/backend-core/commit/56ffe6789ac41973a0829855bde16814a6d161e4))

---

## [2.5.0](https://gitlab.com/tms-elte/backend-core/compare/v2.4.2...v2.5.0) (2022-03-28)
### Features

* Add support for image update from remote docker repository ([bae58f](https://gitlab.com/tms-elte/backend-core/commit/bae58fda3b8be9788c3356ea87b8b3da28780bcf))
* Plagiarism basefiles and results download ([c3010a](https://gitlab.com/tms-elte/backend-core/commit/c3010a07e9a4b46b937343c0871597e16dffc078))

### Bug Fixes

* Always store detailed compile or runtime error messages for the evaluator ([f4fe04](https://gitlab.com/tms-elte/backend-core/commit/f4fe048231c5a012e506f49e133930c71bdc0bf8))
* Remove FL_NOCASE flag when opening ZIP ([527990](https://gitlab.com/tms-elte/backend-core/commit/52799035709f62f6e0ab32e378973d5b9cb9e7ad))
* Return display name with LDAP-based auth properly. ([1d702a](https://gitlab.com/tms-elte/backend-core/commit/1d702ad4b0690f11c90b0e56389ba0d52a818c7a))

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

