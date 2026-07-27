# Taste (Continuously Learned by [CommandCode][cmd])

[cmd]: https://commandcode.ai/

# workflow
- When removing or replacing a feature, clean up ALL related references (imports, constants, usages) before running tests — don't run tests mid-cleanup. Confidence: 0.80
- Often provides exact code (schemas, implementations) verbatim and expects the assistant to create files faithfully from that code, not reinterpret or modify it. Confidence: 0.70

# communication
- Prefers short, direct imperative commands ("Run all the tests", "Tell me what are all the test for this proyect") and expects the assistant to execute and report back with clear results. Confidence: 0.75
- When learning a new process, appreciates detailed step-by-step walkthroughs with concrete examples (methods, URLs, payloads, expected responses). Confidence: 0.75

# testing
- Tests must never hit an actual database; they should use in-memory SQLite (or equivalent isolated store) for full isolation from production/dev infrastructure. Confidence: 0.90
- Uses Postman for manual API endpoint testing and validation. Confidence: 0.70

# entities
- Entities should only depend on Eloquent and represent database objects, with no other application or infrastructure dependencies. Confidence: 0.70

# skills
- SKILL.md files should start with YAML frontmatter block containing name, description, and allowed-tools fields, followed by markdown body content. Confidence: 0.70
- AGENTS.md files should include a ## Skills section with links to relevant .commandcode/skills/ skill files. Confidence: 0.70
