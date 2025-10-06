# Lemmon Validator - Development Roadmap

This roadmap outlines the strategic development plan for future releases, prioritizing features that deliver maximum value to the PHP validation ecosystem.

## ✅ Recently Completed (v0.4.0) - Static Logical Combinators

### Advanced Validation Logic
- [x] ✅ **Static Logical Combinators** - `Validator::anyOf()`, `Validator::allOf()`, `Validator::not()` for type-agnostic validation
- [x] ✅ **Enhanced Mixed-Type Support** - Clean syntax for arrays with mixed item types
- [x] ✅ **Comprehensive Documentation** - Complete API reference and practical examples
- [x] ✅ **Comprehensive Test Suite** - 19 new tests (76 total) with 54 new assertions (208 total)

## 📋 Next Release (v0.5.0) - Utility Features

### String Enhancements
- [ ] **`time()`** - Validates time format (HH:MM:SS, HH:MM)
- [ ] **`regex()` alias** - Alternative name for `pattern()` method for clarity

### String Transformations
- [ ] **`trim()`** - Removes leading/trailing whitespace (extremely common)
- [ ] **`normalizeSpaces()`** - Collapses multiple spaces into single spaces (complex pattern)
- [ ] **`slugify()`** - Convert to URL-friendly slugs (complex: accents, case, special chars)
- [ ] **`toCamelCase()`** / **`toSnakeCase()`** - Advanced case formatting (complex logic)
- [ ] **`transform()`** - Generic method for custom transformations (core functionality)

### Array Enhancements
- [ ] **`uniqueItems()`** - Validates that all array items are unique
- [ ] **`minItems()`** / **`maxItems()`** - Array length constraints
- [ ] **`contains()`** - Validates array contains specific item

### Array Transformations
- [ ] **`filterEmpty()`** - Remove null/empty values (specific filtering logic)
- [ ] **`unique()`** - Remove duplicates while preserving structure
- [ ] **`flatten()`** - Flatten nested arrays (complex recursive logic)
- [ ] **`transform()`** - Generic method for custom transformations (core functionality)

### Numeric Enhancements
- [ ] **`nonNegative()`** - Validates numbers >= 0 (includes zero)
- [ ] **`nonPositive()`** - Validates numbers <= 0 (includes zero)
- [ ] **`gt()`** / **`gte()`** - Greater than / greater than or equal explicit comparisons
- [ ] **`lt()`** / **`lte()`** - Less than / less than or equal explicit comparisons

### Numeric Transformations
- [ ] **`clamp(min, max)`** - Restrict numbers to range (not obvious: max(min, min(max, value)))
- [ ] **`round(precision)`** - Round with precision parameter (convenience for common pattern)
- [ ] **`transform()`** - Generic method for custom transformations (core functionality)

### Universal Validators
- [ ] **`enum()`** - Validates value is one of predefined options (available on all validators)
- [ ] **`const()`** - Validates value equals specific constant

### Universal Transformations
- [ ] **`transform()`** - Generic transformation method (available on all validators)
- [ ] **`when(condition, transform)`** - Conditional transformations
- [ ] **`pipe()`** - Alias for transform() with functional programming style

### Enhanced Error Handling
- [ ] **Structured error codes** - Programmatic error identification
- [ ] **Error path enhancement** - Full field paths for nested validation errors

## 🏗️ Future Release (v0.6.0) - Advanced Schema Features

### Schema Composition
- [ ] **`additionalProperties()`** - Control undefined properties in schemas
- [ ] **`patternProperties()`** - Validate properties matching regex patterns
- [ ] **`propertyNames()`** - Validate property names themselves
- [ ] **`dependencies()`** - Conditional validation based on other properties

### Schema Manipulation
- [ ] **`partial()`** - Make all schema fields optional
- [ ] **`pick()`** - Create schema with only specified fields
- [ ] **`omit()`** - Create schema excluding specified fields
- [ ] **`merge()`** - Combine multiple schemas

### Advanced Array Features
- [ ] **`additionalItems()`** - Control validation of extra array items
- [ ] **Tuple validation** - Fixed-position array item validation

## 🔮 Long-term Vision (v1.0.0+)

### Performance & Scalability
- [ ] **Validation caching** - Cache expensive validation results
- [ ] **Lazy evaluation** - Optimize validation chains
- [ ] **Parallel validation** - Concurrent validation for independent rules

### Developer Experience
- [ ] **Schema serialization** - Export/import schemas as JSON
- [ ] **TypeScript definitions** - Generate TS types from PHP schemas
- [ ] **IDE integration** - Enhanced autocomplete and validation

### Framework Integration
- [ ] **Laravel integration** - Native Laravel validator bridge
- [ ] **Symfony integration** - Symfony Form component compatibility
- [ ] **PSR-7 middleware** - HTTP request validation middleware

## 🎯 Ongoing Quality Initiatives

### Documentation
- [x] ✅ Comprehensive guide restructure
- [x] ✅ API reference documentation
- [x] ✅ Real-world examples
- [ ] **Video tutorials** - Getting started screencasts
- [ ] **Interactive documentation** - Runnable examples

### Testing & Quality
- [x] ✅ Organized test suite (9 focused test files, 76 tests, 208 assertions)
- [x] ✅ 100% PHPStan compliance
- [x] ✅ PHP-CS-Fixer standards
- [x] ✅ Static logical combinators test coverage
- [ ] **Mutation testing** - Enhanced test quality verification
- [ ] **Property-based testing** - Randomized validation testing
- [ ] **Performance benchmarking** - Continuous performance monitoring

### Community & Ecosystem
- [ ] **Plugin architecture** - Third-party validator extensions
- [ ] **Community validators** - Shared validation library
- [ ] **Migration tools** - Automated upgrades between versions

## 🔄 Development Process

### Feature Development Workflow
1. **Research & Design** - Community input, API design
2. **Implementation** - Core functionality with tests
3. **Documentation** - Comprehensive guides and examples
4. **Review & Testing** - Code review, edge case testing
5. **Release & Feedback** - Version release, community feedback

### Quality Gates
- ✅ **100% test coverage** for new features
- ✅ **PHPStan level max** compliance
- ✅ **Backward compatibility** preservation
- ✅ **Performance benchmarking** - No regressions
- ✅ **Documentation completeness** - Guides and examples

### Release Cadence
- **Major releases** (x.0.0) - Breaking changes, major features (quarterly)
- **Minor releases** (x.y.0) - New features, enhancements (monthly)
- **Patch releases** (x.y.z) - Bug fixes, documentation (as needed)

## 📊 Success Metrics

### Adoption Metrics
- **Package downloads** - Packagist installation statistics
- **GitHub stars** - Community interest and engagement
- **Issue resolution** - Response time and resolution rate

### Quality Metrics
- **Test coverage** - Maintain >95% code coverage
- **Static analysis** - Zero PHPStan errors at max level
- **Performance** - Validation speed benchmarks
- **Documentation** - Comprehensive coverage of all features

### Community Health
- **Contribution guidelines** - Clear contributor onboarding
- **Code of conduct** - Inclusive community standards
- **Regular releases** - Consistent development momentum

---

This roadmap is reviewed quarterly and updated based on community feedback, usage patterns, and emerging PHP ecosystem trends. Feature priorities may be adjusted based on user demand and contribution availability.
