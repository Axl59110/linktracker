# STORY-{id}: {Title}

**Epic:** {Epic Name}
**Priority:** {Must Have | Should Have | Could Have}
**Points:** {1|2|3|5|8|13}
**Status:** {not_started | in_progress | completed | blocked}
**Assignee:** {Name}

---

## User Story

En tant que {user role}
Je veux {action/feature}
Afin de {benefit/value}

---

## Context & Background

{Provide context about why this story is needed. What problem does it solve? What value does it bring?}

---

## Acceptance Criteria

### AC1: {Criterion Title}
- [ ] {Specific, testable condition 1}
- [ ] {Specific, testable condition 2}
- [ ] {Specific, testable condition 3}

### AC2: {Criterion Title}
- [ ] {Specific, testable condition}

### AC3: {Criterion Title}
- [ ] {Specific, testable condition}

---

## Technical Specification

### Affected Components
- **Backend:** {List of backend files/components}
- **Frontend:** {List of frontend files/components}
- **Database:** {Database changes if any}
- **API:** {New or modified endpoints}

### Endpoints (if applicable)
```
POST   /api/{resource} - {Description}
GET    /api/{resource} - {Description}
PUT    /api/{resource}/{id} - {Description}
DELETE /api/{resource}/{id} - {Description}
```

### Database Schema (if applicable)
```sql
CREATE TABLE {table_name} (
    id UUID PRIMARY KEY,
    {column_name} {type} {constraints},
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);
```

### Key Classes/Functions
- `{ClassName}` - {Purpose}
- `{FunctionName}` - {Purpose}

---

## Technical Tasks

### Backend
- [ ] {Task 1}
- [ ] {Task 2}
- [ ] {Task 3}

### Frontend
- [ ] {Task 1}
- [ ] {Task 2}

### Testing
- [ ] {Unit tests for X}
- [ ] {Integration tests for Y}
- [ ] {E2E tests for Z}

### Documentation
- [ ] {Update README if needed}
- [ ] {Update API docs}
- [ ] {Add inline comments for complex logic}

---

## Dependencies

### Depends On (Blockers)
- **STORY-{id}** - {Reason why this blocks current story}

### Blocks (Stories waiting on this)
- **STORY-{id}** - {Reason why dependent on current story}

---

## Mockups / Design

{Link to Figma, screenshots, or wireframes if applicable}

---

## Test Scenarios

### Scenario 1: {Happy Path}
**Given:** {Initial state}
**When:** {Action taken}
**Then:** {Expected result}

### Scenario 2: {Edge Case}
**Given:** {Initial state}
**When:** {Action taken}
**Then:** {Expected result}

### Scenario 3: {Error Case}
**Given:** {Initial state}
**When:** {Action taken}
**Then:** {Expected error/behavior}

---

## Risks & Considerations

### Technical Risks
- **{Risk 1}** - {Description and mitigation}
- **{Risk 2}** - {Description and mitigation}

### Security Considerations
- {Security concern 1 and how it's addressed}
- {Security concern 2 and how it's addressed}

### Performance Considerations
- {Performance concern and optimization strategy}

---

## Definition of Done

**Code:**
- [ ] Code written and follows project conventions
- [ ] Code reviewed (self or peer)
- [ ] No commented-out code or debug statements
- [ ] No critical TODOs left unresolved

**Tests:**
- [ ] Unit tests written and passing
- [ ] Integration tests written and passing
- [ ] Test coverage >= 80%
- [ ] All tests in CI passing

**Documentation:**
- [ ] Inline comments for complex logic
- [ ] README updated if needed
- [ ] API documentation updated
- [ ] CHANGELOG updated

**Quality:**
- [ ] No regressions found
- [ ] Performance acceptable (< 500ms for API calls)
- [ ] Security reviewed (no OWASP Top 10 vulnerabilities)
- [ ] Accessible (WCAG AA for frontend)

**Deployment:**
- [ ] Migrations created (if DB changes)
- [ ] Seeds updated (if needed)
- [ ] .env.example updated (if new config)
- [ ] Build passes in CI

**Acceptance:**
- [ ] All acceptance criteria validated
- [ ] Story reviewed by code-reviewer agent
- [ ] Story validated by story-validator agent

---

## Progress Tracking

### Status History
- **{Date}** - Created
- **{Date}** - Started (status: in_progress)
- **{Date}** - Completed (status: completed)

### Time Tracking
**Estimated:** {points} points
**Actual:** {actual_points} points
**Started:** {YYYY-MM-DD HH:MM:SS}
**Completed:** {YYYY-MM-DD HH:MM:SS}
**Duration:** {hours} hours

---

## Implementation Notes

{This section is filled out during/after implementation}

### Completed: {YYYY-MM-DD}
**Branch:** feature/STORY-{id}-{slug}
**Commits:** {list of commit hashes or PR link}

### Files Created
- `{file_path}` - {Description}
- `{file_path}` - {Description}

### Files Modified
- `{file_path}` - {What changed}
- `{file_path}` - {What changed}

### Key Decisions
1. **{Decision 1}:** {Rationale}
2. **{Decision 2}:** {Rationale}

### Tests Written
- {Number} unit tests
- {Number} integration tests
- {Number} E2E tests
- **Coverage:** {percentage}%

### Performance Metrics
- {Endpoint/Function}: avg {time}ms
- {Database queries}: {count} queries
- {Memory usage}: {amount}MB

### Security Implementation
- {Security measure 1 implemented}
- {Security measure 2 implemented}

### Known Issues / Tech Debt
- {Issue 1 and plan to address}
- {Tech debt item and reason for deferring}

### Lessons Learned
- {Learning 1}
- {Learning 2}

---

## Related Stories

- **STORY-{id}** - {Related story title}
- **STORY-{id}** - {Related story title}

---

## References

- [PRD Section]({link})
- [Architecture Doc]({link})
- [External Documentation]({link})
- [Design File]({link})

---

## Comments / Discussion

{Space for comments, questions, clarifications during implementation}
