https://github.com/tanveeratlogicx/VAPTCopilot/blob/main/ReadmeIns.md

We need to develop a WordPress Plugin Builder [VAP Secure] from an uploaded [SixTee-Risk-Catalogue](T:\~\Local925 Sites\hermasnet\app\public\wp-content\plugins\VAPTBuilder\data\WIP\VAPT-SixTee-Risk-Catalogue-12-EntReady_v3.4.json) JSON file. The Structured JSON file contains details of the VATP risk in "risk_catalog", and contains following additional nodes like 
  1. *metadata*: The metadata node serves as the document header and system manifest, providing essential identification, versioning, and quality assurance information about the entire risk catalog.
  2. *risk_catalog*: The risk_catalog is the core security database containing detailed specifications for all WordPress vulnerabilities, acting as the single source of truth for protection implementation.
  3. *ai_agent_instructions*: This section serves as a comprehensive prompt engineering guide for AI agents (like Antigravity, Perplexity, Kimi etc) to generate production-ready React components for the WordPress VAPT Protection Plugin admin interface. 
  4. *global_settings*: This is the central configuration hub for the entire VAPT Protection Plugin, defining system-wide behaviors, operational parameters, and integration settings. 

The plugin must adhere to the WordPress Coding Standards and follow the Best Practices as being practiced in the industry. We should use Ajax through out the development of the plugin across all the Interface Related Elements. The development Lifecycle consists of Draft[Loading of Featrues from the Database, and displaying them in a Table Format], Develop, Release. A versioning system is need to put in place consisting of Major, Minor and Patch nodes as a standard - and we need to adopt Version Bump mechanism which consistently keeps updating the Version with each change.

The plugin need to have the following Sub-Menus VAPT Secure Dashboard[Superadmin Only], VAPT Secure Workbench[Superadmin Only], Under the VAPT Secure Main Menu [Superadmin and WordPress Admin].

VAPT Secure Dashboard and VAPT Secure Workbench, will only be visible to a Superadmin user tanmalik786, email tanmalik786@gmail.com should be validated by an email OTP BUT could be skipped on the localhost to avoid any Inconveniences. Each loaded feature from the Datasource [JSON File] will have a Status of Draft, and will Transition through these lifecycle steps/statusses Develop and Release. Both of these Menu items will only be visible to this Superadmin user, and shall remain hidden from the Website Admins and could be access using a special URL.

VAPT Master Dashboard, will consist of a header section - containing things related to JSON file uploading, hiding the other uploaded files, Displaying a summary of things like Feature Count, Count of Features at states like Draft, Build, Test, Release - see the attached image.

and four tabs structure - as described below

Feature List - will display all the feature as defined in the uploaded JSON file with columns like Feature Name, Category, Severity, Description, Lifecycle Status, Released Date, Includes.
License Management - Standard [30 Days, Pro One Year and Developer Perpetual], will be tied to the selected domain.
Domain Features - Here Superadmin will pick and chose which of the Features with a Status of Release, will actually be released to this Domain [License Domain].
Build Generator - this tab will be used to actually create a new plugin for release to the Site Owner - with some white label features for the Plugin Header BUT tied to the selected domain and will offer only the features released for this domain.
The Include column will contain Toggle's like Include Test tests to include the steps the end user can adopt to verify, 2. License Management - Standard [30 Days, Pro One Year and Developer Perpetual], will be tied to the selected domain. 3. Domain Features - Here Superadmin will pick and chose which of the Features with a Status of Release, will actually be released to this Domain [License Domain]. 4. Build Generator - this tab will be used to actually create a new plugin for release to the Site Owner - with some white label features for the Plugin Header BUT tied to the selected domain and will offer only the features released for this domain.

The Include column will contain Toggle's like Include Test tests to include the steps the end user can adopt to verify,

Can you analyze for first 5 features and suggest me a plan as to how we can use this JSON file to create an "AI Design Prompt", which when shared a AI Agent, helps it creates a JSON Schema to Create Functional Implementation and a Corresponding Verification Implementation to use as an evidence that the Functional Implementation Actually Works?
