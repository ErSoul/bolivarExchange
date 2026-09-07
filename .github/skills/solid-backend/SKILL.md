---
name: solid-backend
description: Enforce SOLID backend design by extracting business logic from controllers into services, keeping controllers thin, and validating behavior with focused tests.
---

# SOLID Backend Refactor

## When to use
Use this skill when a controller is doing business rules, calculations, data transformations, or orchestration that belongs to application logic rather than HTTP handling.

## Goal
Keep the backend aligned with SOLID principles, especially Single Responsibility Principle (SRP), by moving domain logic out of controller actions and into services that can be reused, tested, and evolved independently.

## Workflow

1. Inspect the controller action and identify business logic.
   - Look for calculations, state changes, validation rules, pricing logic, conversion logic, mapping logic, and cross-model coordination.
   - If logic depends on domain rules instead of request/response handling, it should live outside the controller.

2. Decide the right extraction point.
   - Use a service for reusable business logic.
   - Use a request class for input validation.
   - Use a model or repository only for data access concerns.
   - Keep controllers focused on: reading input, calling services, and returning a response.

3. Extract business logic into a dedicated service class.
   - Create an app/Services/… class if one does not already exist.
   - Move calculation and decision logic there.
   - Keep the service stateless when possible.
   - Return plain arrays/collections or DTO-like arrays so the controller can pass them to the view or response.

4. Refactor the controller.
   - Inject or resolve the service via constructor injection when possible.
   - Replace inline logic with a single service call.
   - Keep the controller action readable and descriptive.

5. Verify behavior with tests.
   - Add or update a focused feature test that verifies the real output.
   - Prefer a failing regression test before the final fix when a bug exists.
   - Confirm the controller still returns the same user-facing result after extraction.

6. Review the final design against SOLID.
   - SRP: one reason to change for each class.
   - OCP: extend behavior through new policies/services instead of editing old logic in place.
   - LSP: avoid brittle abstractions that break callers.
   - ISP: keep interfaces minimal and focused.
   - DIP: depend on abstractions/services, not concrete details embedded in the controller.

## Decision points

- If the logic is tied to HTTP request handling, keep it in the controller only when it is trivial.
- If the logic transforms domain data or demands business rules, move it to a service.
- If the logic reads or writes persistence, consider a repository or model method only after the service narrows the domain behavior.
- If validation is about request constraints, place it in Form Request classes, not in the controller.

## Completion checklist
A task is complete when all of the following are true:

- The controller no longer contains core business calculations.
- The extracted logic lives in a dedicated service or equivalent domain boundary.
- The controller focuses on orchestration and response creation.
- Request validation is separated from business logic.
- Relevant tests pass and cover the extracted behavior.

## Example prompts
- "Refactor this controller so it no longer contains business logic; move the pricing calculation into a service."
- "Apply SOLID principles to this backend endpoint and keep the controller thin."
- "Extract the currency conversion rules from the controller into a service and validate the response with tests."

## Related customizations
- Create a companion skill for request validation and form request cleanup.
- Create a testing skill focused on high-value regression tests for service refactors.
- Add a skill for repository extraction when persistence logic is mixed with controller logic.
