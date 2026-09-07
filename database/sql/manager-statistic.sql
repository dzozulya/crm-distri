SELECT
    m.id,
    m.name,

    COUNT(l.id) FILTER (
        WHERE l.status IN ('NEW', 'IN_PROGRESS')
    ) AS open_leads_count,

    AVG(
        EXTRACT(
            EPOCH FROM (
                CURRENT_TIMESTAMP - l.updated_at
            )
        )
    ) FILTER (
        WHERE l.status = 'IN_PROGRESS'
    ) AS average_time_in_work_seconds,

    COUNT(l.id) FILTER (
        WHERE l.status = 'DONE'
          AND l.updated_at >= CURRENT_TIMESTAMP - INTERVAL '30 days'
    ) AS completed_last_30_days

FROM managers AS m

         LEFT JOIN leads AS l
                   ON l.manager_id = m.id

GROUP BY
    m.id,
    m.name

ORDER BY
    m.id;
