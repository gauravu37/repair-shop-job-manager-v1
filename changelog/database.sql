ALTER TABLE wp_rsjm_jobs
ADD discount_type VARCHAR(20) DEFAULT 'amount',
ADD discount_value DECIMAL(10,2) DEFAULT 0,
ADD discount_amount DECIMAL(10,2) DEFAULT 0;


ALTER TABLE wp_rsjm_jobs
ADD job_type VARCHAR(20) NOT NULL DEFAULT 'repair';