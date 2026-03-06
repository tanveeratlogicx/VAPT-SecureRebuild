# Implementation Plan - Terminal Command Workspace Rule

This plan outlines the creation of an intelligent workspace rule to auto-approve terminal commands while maintaining a Deny List for sensitive operations.

## Proposed Changes

### Configuration Files

- **[NEW] [.agent/rules/terminal-command-policy.md](file:///t:/~/Local925%20Sites/hermasnet/app/public/wp-content/plugins/VAPT-Secure/.agent/rules/terminal-command-policy.md)**: Defines the logic for auto-approval.
- **[NEW] [.agent/rules/allow-list.md](file:///t:/~/Local925%20Sites/hermasnet/app/public/wp-content/plugins/VAPT-Secure/.agent/rules/allow-list.md)**: A collection of frequently used and safe commands that are explicitly allowed.
- **[NEW] [.agent/rules/deny-list.md](file:///t:/~/Local925%20Sites/hermasnet/app/public/wp-content/plugins/VAPT-Secure/.agent/rules/deny-list.md)**: A collection of high-risk commands that REQUIRE manual approval.

### Rule Logic

1. **Check Deny List**: If a command matches a pattern in `deny-list.md`, it MUST NOT be auto-run (`SafeToAutoRun: false`).
2. **Auto-Approve Others**: All other commands are considered "Allow List" by default and can be auto-run (`SafeToAutoRun: true`).
3. **Notification**: Whenever a command is auto-run, the assistant must explicitly mention it in the task status or as a note in the chat (if applicable) to ensure the user is aware.
4. **Intelligent Update**: If a command is proposed without auto-run and the user approves it, the assistant should append it to `allow-list.md` if it's not already there and not in the deny list.

## Verification Plan

### Manual Verification

- Propose a safe command (e.g., `git status`) and ensure it is marked as safe.
- Propose a command in the newly created Deny List and ensure it requires approval.
- Verify that manually approved commands are added to the Allow List.

## Revision History

### 20260306_@1443

- Initial plan for terminal command policy.

### 20260306_@1441

- Added notification requirement for auto-run commands per user feedback.
