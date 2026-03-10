---
description: When a Feature is Transitioned from `Draft to Develop`
---

* Set context to @VAPT-Secure

* While creating instructions for `Development Instructions (AI Guidance)`, do keep an eye on the JSON fields for context-aware prompts which have already been selected [Mapping Configuration] Modal and optimized to help achieve a high AI Output Quality Accuracy score - which is our core objective.

* Prepare a plan to review the creation of the `Development Instructions (AI Guidance)` instructions in accordance with the recommendations in the latest `VAPT-Secure\data\VAPT_AI_Agent_System_README_v*.md` - which Contains updated information about the Schema First Architecture's updated Guidelines - Transition to Develop Modal.

1. The files must be read in the Order stated below:
The agent instructions explicitly define the order matters:

| Step | File | Description |
|------|------|-------------|
| 1    | ai_agent_instructions | Load the rulebook: naming conventions, htaccess_syntax_guard, rubric, balanced_protection_policy, task steps |
| 2    | interface_schema | Load the blueprint for the risk_id: ui_layout, components, severity, available_platforms, code_refs |
| 3    | enforcer_pattern_library | Load the actual enforcement code via lib_key from Step 2

                                   via lib_key from Step 2)

Step 4 → Self-check against rubric from Step 1 → score ≥18 → deliver

* The plan should include a section ## 📁 Target Configuration Files section strictly mandating the AI to declare the exact configuration file paths like (.htaccess, wp-config.php, functions.php, ..), the driver must write the protection to.
