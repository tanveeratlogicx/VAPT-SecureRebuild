# Terminal Command Deny List

Commands matching these patterns REQUIRE manual user approval (`SafeToAutoRun: false`).

- `rm`
- `del`
- `git reset --hard`
- `git clean -f`
- `git push`
- `npm publish`
- `composer install` (if it might overwrite local changes)
- `drop table`
- `delete from`
