## Context

Closes <!-- add issue id here -->

<!-- Mention the front branch that should be used to test if it is not develop -->

## Validation

<!-- List here the steps to test if it is not trivial, don't hesitate to add unusual cases -->

## Out of scope changes

<!-- Mention here all changes that were not directly linked to the issue -->

## Developer checklist

- [ ] Process check
  - [ ] The related issue id is mentioned in the title of the Merge request
  - [ ] The related issue id is mentioned the **Context** section with the "Closes" mention
  - [ ] The related issue is in `Code review` status
  - [ ] The `API` component has been added to the related issue
  - [ ] The product label has been added to the merge request
- [ ] Branch is up to date with target branch
- [ ] The `front` branch used to test is indicated in the **Context** section
- [ ] The dependencies have been checked
  - [ ] If necessary, the label has been added
  - [ ] If necessary, a link to the merge request(s) has been added to the **Context** section
- [ ] History is clean and there are no duplicated commit messages
- [ ] Steps to test and tricky situations have been added to the **Validation** section
- [ ] Out of scope changes have been listed in the dedicated section
- [ ] A test has been written to avoid new occurrences of that bug

## Reviewer checklist

- [ ] The merge request has been assigned
- [ ] The original issue has been read
  - [ ] The problem has been understood
  - [ ] Comments have been read so that possible discrepancies are understood
- [ ] Commit messages follow the specification
- [ ] Test coverage is not down
- [ ] The behavior observed in the bugfix is not observed anymore
  - [ ] Nominal behavior is not broken
  - [ ] (Optional) The code has been tested on a live environment
- [ ] The code is documented
- [ ] Any addition of a new library has been validated with the team
