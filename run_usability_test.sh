#!/bin/bash
echo "=== USABILITY TEST EXECUTION ==="
echo ""

# 1. Screen Resolution Test
echo "1. Testing Multiple Screen Resolutions..."
echo "   Desktop (1200px):" && curl -s "http://localhost:8000" | grep -c "container\|row" | xargs echo "   Elements found:"
echo "   Mobile (375px):" && curl -s -H "User-Agent: Mozilla/5.0 (iPhone)" "http://localhost:8000" | grep -c "meta.*viewport\|mobile" | xargs echo "   Mobile tags found:"

# 2. Color Analysis
echo ""
echo "2. Color Scheme Analysis..."
echo "   Status colors from README:"
echo "   - Normal: #198754 (green)"
echo "   - Warning: #fd7e14 (orange)"
echo "   - Critical: #dc3545 (red)"
echo "   Checking CSS for color usage..."
curl -s http://localhost:8000 | grep -o "#[0-9a-fA-F]\{6\}" | sort | uniq

# 3. Performance Metrics
echo ""
echo "3. Performance Checks..."
echo "   Page load time:" && time curl -s -o /dev/null -w "Total: %{time_total}s\n" http://localhost:8000
echo "   Total page size:" && curl -s -o /dev/null -w "Size: %{size_download} bytes\n" http://localhost:8000

# 4. Generate Test Report Template
echo ""
echo "4. Generating Test Report Template..."
cat > usability_testing/final_report.md << 'EOF'
# Usability Test Report
## Temperature Monitoring System
### Test Date: $(date +%Y-%m-%d)
### Participants: 3-5 users

## EXECUTIVE SUMMARY
[Overall usability assessment]

## METHODOLOGY
- Heuristic Evaluation (Nielsen's 10 principles)
- Task Completion Testing
- SUS Questionnaire
- Responsive Design Testing
- Accessibility Evaluation

## KEY FINDINGS

### Strengths:
1.
2.
3.

### Areas for Improvement:
1.
2.
3.

## DETAILED RESULTS

### 1. Heuristic Evaluation Score: ___/50
- Visibility: ___/5
- Real World Match: ___/5
- User Control: ___/5
- Consistency: ___/5
- Error Prevention: ___/5
- Recognition: ___/5
- Efficiency: ___/5
- Aesthetic: ___/5
- Error Recovery: ___/5
- Help: ___/5

### 2. Task Completion Rates:
| Task | Success Rate | Avg Time | Difficulty |
|------|--------------|----------|------------|
| Status Check | % | seconds | [1-5] |
| Historical Analysis | % | seconds | [1-5] |
| Alert Response | % | seconds | [1-5] |
| Mobile Operation | % | seconds | [1-5] |

### 3. SUS Score: ___ (Interpretation: _______)

### 4. Responsive Design: ___/6 viewports passed

### 5. Accessibility: ___/10 WCAG criteria met

## RECOMMENDATIONS
### High Priority:
1.
2.

### Medium Priority:
1.
2.

### Low Priority:
1.
2.

## APPENDICES
- Screenshots directory: usability_testing/screenshots/
- Participant data: usability_testing/participant_data/
- Raw test logs: usability_testing/logs/
EOF

echo "✅ Test setup complete!"
echo "👉 Next steps:"
echo "   1. Recruit 3-5 participants"
echo "   2. Conduct task completion tests"
echo "   3. Fill heuristic evaluation"
echo "   4. Calculate SUS scores"
echo "   5. Compile final report"
echo ""
echo "Files created in 'usability_testing/' directory"
