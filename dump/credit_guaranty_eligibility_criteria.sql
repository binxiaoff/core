INSERT INTO credit_guaranty_field_configuration (public_id, field_alias, category, type, target_property_access_path, comparable, unit, predefined_items) VALUES
('77d246f8-8181-4d45-9a2a-268dd6795e70', 'juridical_person', 'general', 'bool', '', 0, NULL, NULL),
('3e2201f1-493f-475d-b84f-ee44e9065ea2', 'on_going_creation', 'general', 'bool', '', 0, NULL, NULL),
('a5ebc5fa-ebd6-450e-9c44-1aab84e65bbb', 'receiving_grant', 'general', 'bool', '', 0, NULL, NULL),
('0393c13d-1511-4d60-975e-ead448ed5d13', 'subsidiary', 'general', 'bool', '', 0, NULL, NULL),
('46c2d1b3-61fa-4d2f-a3f3-0336feecd2e2', 'borrower_type', 'profile', 'list', 'Unilend\\CreditGuaranty\\Entity\\Borrower::type', 0, NULL, NULL),
('56d4b239-8b5a-41f0-9e65-4ced292b0c0c', 'company_name', 'profile', 'other', '', 0, NULL, NULL),
('bc84acbb-e1fe-4878-9e7b-7999c7a38282', 'company_address', 'profile', 'other', '', 0, NULL, NULL),
('4bd9fc81-aaaa-4753-913e-86c6b193fd85', 'borrower_identity', 'profile', 'other', '', 0, NULL, NULL),
('8f86ff38-1bfa-4608-a74d-7a40052d3f41', 'beneficiary_address', 'profile', 'other', '', 0, NULL, NULL),
('093a2142-ab5d-4b57-afb0-e8749131740b', 'tax_number', 'profile', 'other', '', 0, NULL, NULL);