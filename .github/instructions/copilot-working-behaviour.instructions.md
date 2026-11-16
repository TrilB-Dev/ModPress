# 19 Copilot Working Behaviour

GitHub Copilot must work incrementally, follow the established ModPress architecture, reuse existing systems and stop for clarification when a decision cannot be made safely from the repository and these instructions.

### 19.1 Before Starting Work

Before making changes:

1. Inspect all files relevant to the requested task.
2. Inspect the existing classes, services, repositories, managers, helpers and other systems related to the task.
3. Identify which existing components will be reused or extended.
4. Identify any remaining PluginName, pluginname, PLUGINNAME placeholders that affect the requested work.
5. Check src/Includes/Functions/Helpers/ for existing helper functionality relevant to the task.
6. For database\-related work, inspect: 
    - src/Includes/Core/WP/Database.php
    - src/Includes/Core/Schema.php
    - src/Includes/Core/Defaults.php
7. Briefly describe the intended implementation before making substantial changes.
8. Identify any architectural uncertainty or conflict before proceeding.

Do not begin by creating new abstractions until the existing repository has been inspected for an equivalent implementation.

### 19.2 Implementation Behaviour

When implementing a task:

1. Implement the smallest complete change required to satisfy the task.
2. Reuse existing ModPress abstractions wherever appropriate.
3. Extend existing systems rather than creating parallel implementations.
4. Follow the current ModPress architecture and these development instructions.
5. Backwards compatibility with previous development\-stage implementations is **not required** unless explicitly requested.
6. Remove or replace obsolete development\-stage implementations when they conflict with the architecture defined in these ModPress development instructions (which take precedence over repository code per 19.4) and their replacement is part of the requested work.
7. Keep changes focused on the requested task.
8. Do not regenerate, refactor or modify unrelated files without a demonstrated requirement.
9. Do not silently make architectural decisions that are not established by the repository or these instructions.
10. When uncertain about an architectural decision, requirement, storage responsibility or implementation approach, **stop and ask for clarification before proceeding**.

### 19.3 Existing Helper Usage

The established ModPress helper system is located under:

src/Includes/Functions/Helpers/

These helpers must be inspected before implementing equivalent reusable functionality elsewhere.

Copilot must:

1. Search src/Includes/Functions/Helpers/ for functionality relevant to the current task.
2. Inspect existing usages of the relevant helper to understand how it is intended to be used.
3. Reuse an existing helper when it already provides the required functionality.
4. Extend an existing helper when the new functionality clearly belongs within that helper's existing responsibility.
5. Do not duplicate helper functionality inside services, repositories, controllers, managers, REST components, admin interfaces or templates.
6. Do not create a new helper until the existing helper directory has been checked for equivalent or closely related functionality.
7. Follow the existing helper's established public interface, return behaviour and conventions.
8. Do not move ModPress business logic into a generic helper merely because the logic could technically be reused.
9. If it is unclear whether functionality belongs in an existing helper, a new helper, a service, repository or another component, **stop and ask for clarification**.

Examples of functionality for which existing helpers must be checked include:

- Sanitisation and normalisation.
- Request handling.
- Permission utilities.
- Query utilities.
- Content handling.
- URL generation and manipulation.
- AJAX utilities.
- Form handling.

For example:

- Before manually constructing an ModPress URL, check the existing URL helpers.
- Before implementing common request processing, check the existing request helpers.
- Before creating reusable sanitisation logic, check the existing sanitisation helpers.
- Before creating generic query utilities, check the existing query helpers.
- Before implementing reusable AJAX handling, check the existing AJAX helpers.
- Before implementing reusable form\-processing behaviour, check the existing form helpers.
- Before adding generic permission utilities, check the existing permission helpers.

The required decision order is:

1. **Reuse an existing helper.**
2. **Extend the appropriate existing helper.**
3. **Create a new helper only if no appropriate helper exists.**
4. **Ask for clarification when responsibility is unclear.**

The existence of a helper does not override the responsibilities of repositories, services, models, validators, permission services, database classes or controllers.

### 19.4 Conflict Handling

When Copilot discovers conflicting requirements, implementations, instructions or architectural approaches:

1. Stop work on the affected part of the task.
2. Identify the conflicting files, implementations, instructions or architectural rules.
3. Explain the conflict clearly.
4. Determine whether these ModPress development instructions already define the authoritative behaviour.
5. If these instructions clearly resolve the conflict, follow them and report the conflicting implementation.
6. If these instructions do not clearly resolve the conflict, **stop and ask for clarification**.
7. Do not silently choose between conflicting approaches.
8. If the user explicitly authorises Copilot to decide, choose the approach most consistent with these instructions, state the decision and its rationale explicitly in the response, and record it in the final task summary. A decision stated in this way is not considered silent.
9. Do not combine competing implementations in an attempt to preserve both.
10. Do not preserve an obsolete implementation solely for backwards compatibility while ModPress remains under active development.
11. Do not delete or substantially restructure conflicting code until the intended approach has been established.
12. Once the conflict has been resolved, implement the selected approach consistently.
13. Remove obsolete development\-stage code where appropriate and where removal is within the scope of the task.
14. Report the conflict and its resolution in the final task summary.

Where repository code directly conflicts with these ModPress development instructions, **these instructions take precedence**.

If it is unclear whether a genuine conflict exists or which rule should take precedence, **stop and ask for clarification**.

### 19.5 Error Loop and Failure Handling

Copilot must not remain trapped in repeated errors, failed commands, unsuccessful fixes or circular debugging.

When an error occurs:

1. Read the complete relevant error message and output.
2. Identify the likely source before modifying code.
3. Make a targeted correction based on the available evidence.
4. Do not repeatedly apply the same fix.
5. Do not repeatedly execute the same failing command without making a meaningful change that could affect the result.
6. If successive attempts produce the same or substantially equivalent error, treat the situation as a potential error loop.
7. If three consecutive fix attempts for the same error produce the same or a substantially equivalent error, or the same command has been run three times without a change in outcome, treat this as an error loop and stop.
8. Do not make increasingly speculative changes simply to continue attempting fixes.
9. Do not modify unrelated files while attempting to resolve the error.
10. Do not hide, suppress or bypass the error merely to make validation appear successful.
11. Do not disable tests, validation, security checks, linting or other safeguards merely to bypass the failure unless explicitly instructed.

When stopping because of an unresolved error, report:

- What operation failed.
- The command or process that failed.
- The relevant error message.
- The files or components involved.
- The likely cause, clearly identified as a diagnosis if it has not been confirmed.
- What troubleshooting has already been attempted.
- The result of each attempt.
- What files were changed during troubleshooting.
- Whether those changes should be retained or reverted.
- The current state of the affected functionality.
- What information, decision or action is required to continue.

After reporting an unresolved error, **stop and wait for further instruction**.

Do not continue retrying the failure or implement an unrelated workaround unless explicitly instructed.

### 19.6 Completing a Task

Before declaring a task complete:

1. Review all files changed during the task.
2. Confirm the implementation follows the current ModPress architecture.
3. Confirm existing helpers and abstractions were reused where appropriate.
4. Confirm no duplicate system or unnecessary abstraction was introduced.
5. Run the appropriate available validation checks.
6. Confirm PHP syntax for affected PHP files.
7. Confirm formatting and other relevant repository checks.
8. Verify that unrelated functionality was not intentionally changed.
9. Report every file created, modified, moved or removed.
10. Report any database or schema\-version impact.
11. Report any helper created, extended or substantially modified.
12. Report the validation commands executed and their results.
13. Report any unresolved warnings, errors, conflicts or architectural decisions.

Do not report a task as successfully completed when required validation is failing.

If a task cannot be safely completed, clearly report why and **stop for further instruction rather than inventing a solution**.

