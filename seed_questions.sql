-- Sample HackerRank Easy Level Coding Questions

INSERT INTO `exams` (`title`, `type`, `duration`, `status`, `total_marks`, `start_date`, `end_date`, `created_by`) VALUES 
('Coding Fundamentals - Easy', 'Coding', 60, 'active', 30, NOW(), DATE_ADD(NOW(), INTERVAL 7 DAY), 1);

SET @exam_id = LAST_INSERT_ID();

-- 1. Solve Me First
INSERT INTO `questions` (`exam_id`, `question_text`, `type`, `coding_template`, `test_case_1_input`, `test_case_1_output`, `test_case_2_input`, `test_case_2_output`, `test_case_3_input`, `test_case_3_output`, `marks`) 
VALUES (
    @exam_id, 
    'Complete the function solveMeFirst to compute the sum of two integers.\n\nInput Format:\nTwo integers on separate lines.\n\nOutput Format:\nSum of the two integers.', 
    'Coding', 
    '// Complete the solveMeFirst function\nimport java.util.*;\n\npublic class Main {\n    public static void main(String[] args) {\n        Scanner sc = new Scanner(System.in);\n        int a = sc.nextInt();\n        int b = sc.nextInt();\n        System.out.println(a + b);\n    }\n}', 
    '2\n3', '5', 
    '10\n20', '30', 
    '1\n1', '2', 
    10
);

-- 2. Odd or Even (Weird Number)
INSERT INTO `questions` (`exam_id`, `question_text`, `type`, `coding_template`, `test_case_1_input`, `test_case_1_output`, `test_case_2_input`, `test_case_2_output`, `test_case_3_input`, `test_case_3_output`, `marks`) 
VALUES (
    @exam_id, 
    'Given an integer, n, perform the following conditional actions:\n- If n is odd, print Weird\n- If n is even and in the inclusive range of 2 to 5, print Not Weird\n- If n is even and in the inclusive range of 6 to 20, print Weird\n- If n is even and greater than 20, print Not Weird', 
    'Coding', 
    '# Complete the program logic below\nimport sys\n\nfor line in sys.stdin:\n    n = int(line.strip())\n    # Your logic here\n', 
    '3', 'Weird', 
    '24', 'Not Weird', 
    '4', 'Not Weird', 
    10
);

-- 3. Simple Array Sum
INSERT INTO `questions` (`exam_id`, `question_text`, `type`, `coding_template`, `test_case_1_input`, `test_case_1_output`, `test_case_2_input`, `test_case_2_output`, `test_case_3_input`, `test_case_3_output`, `marks`) 
VALUES (
    @exam_id, 
    'Given an array of integers, find the sum of its elements.\n\nInput Format:\nThe first line contains an integer, n (size of array).\nThe second line contains n space-separated integers.', 
    'Coding', 
    '# Python 3 template\nn = int(input())\narr = list(map(int, input().split()))\nprint(sum(arr))', 
    '6\n1 2 3 4 10 11', '31', 
    '3\n1 2 3', '6', 
    '1\n100', '100', 
    10
);
