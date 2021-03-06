SELECT e.event_id AS id, e.event_date AS date, e.event_time AS time, e.end_time, e.docket,
e.proceeding_id  AS event_type_id, p.type, e.language_id, l.name language,
e.judge_id, j.lastname judge_lastname, j.firstname judge_firstname,

e.req_date submission_date ,
e.req_time submission_time,
e.req_by submitter_id,
e.req_class submitter_hat_id,
rc.type AS submitter_hat,
g.flavor AS submitter_group,
CASE
    WHEN e.req_by = 0
    THEN "[anonymous]"
    WHEN e.req_class IN (2,5,6)
    THEN CONCAT('lastname: ',ru.lastname, '; firstname: ',ru.firstname)
    WHEN e.req_class IN (1,8,10)
    THEN CONCAT('lastname: ',rb.lastname, '; firstname: ',rb.firstname)
    WHEN e.req_class = 4
    THEN CONCAT('lastname: ',i.lastname, '; firstname: ',i.firstname)
    WHEN e.req_class = 3
    THEN CONCAT(u_submitter.name, "; staff user")
    ELSE
        "UNKNOWN"
END AS submitter,
g.id AS submitter_group_id,
ru.active AS submitter_active,
ru.email AS submitter_email,
e.created, e.created_by AS created_by_id, u.name AS created_by,
e.lastmod AS modified, e.lastmod_by AS modified_by_id,
u_modifier.name as modified_by,
e.cancel_reason, e.notes AS comments, e.admin_notes AS admin_comments

FROM events e

JOIN proceedings p ON e.proceeding_id = p.proceeding_id
JOIN languages l ON l.lang_id = e.language_id
JOIN judges j ON j.judge_id = e.judge_id
LEFT JOIN request_class rc ON rc.id = e.req_class
LEFT JOIN requests r ON r.event_id = e.event_id
LEFT JOIN request_users ru ON ru.id = e.req_by AND e.req_class IN (2,5,6)
LEFT JOIN request_by rb ON e.req_by = rb.id
LEFT JOIN interpreters i ON e.req_by = i.interp_id
LEFT JOIN groups g ON g.id = ru.group_id AND e.req_class IN (2,5,6)
LEFT JOIN users u_submitter ON u_submitter.user_id = e.req_by /*AND e.req_class = 3*/
LEFT JOIN users u ON u.user_id = e.created_by
LEFT JOIN users u_modifier ON u_modifier.user_id = e.lastmod_by
