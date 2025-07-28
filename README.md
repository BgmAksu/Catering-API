# README DTT BACK END ASSESSMENT #

## Explanations
1. Each facility is linked to a single location (location_id).
2. Each tag can match multiple facilities; their names are unique and reusable.
3. The facility_tags table manages n-n (many-to-many) relationships.
4. ON DELETE CASCADE is used for foreign keys; when the corresponding parent record is deleted, the dependent relationships are also deleted.
