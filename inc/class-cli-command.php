<?php

namespace Publishing_Checklist;

use WP_CLI;
use WP_CLI_Command;

/**
* CLI interface to the Publishing Checklist.
 */
class CLI_Command extends WP_CLI_Command {

	/**
	 * Evaluates publishing checklist for one or more posts.
	 *
	 * ## OPTIONS
	 *
	 * <id>...
	 * : The ID of one or more posts
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific row fields. Defaults to task_id, post_id, status, label, explanation.
	 *
	 * [--format=<format>]
	 * : Accepted values: table, json, csv, summary. Default: table
	 *
	 * ## EXAMPLES
	 *
	 * 	wp checklist evaluate 1
	 *
	 */
	public function evaluate( $args = array(), $assoc_args = array() ) {
		$defaults = array(
			'format'      => 'table',
			'fields'      => array(
				'task_id',
				'post_id',
				'post_title',
				'status',
				'label',
				'explanation',
			),
		);
		$values = wp_parse_args( $assoc_args, $defaults );

		$cli_evaluation = array();
		foreach ( $args as $post_id ) {

			$checklist_data = Publishing_Checklist()->evaluate_checklist( $post_id );

			if ( empty( $checklist_data ) &&  'summary' === $values['format'] ) {
				WP_CLI::warning( sprintf( __( 'No checklist found for %d.', 'publishing-checklist' ), $post_id ) );;
				break;
			}

			if ( 'summary' === $values['format'] ) {
				WP_CLI::success( sprintf( __( '%d of %d tasks complete for %d', 'publishing-checklist' ), count( $checklist_data['completed'] ), count( $checklist_data['tasks'] ), $post_id ) );
			} else {
				foreach ( $checklist_data['tasks'] as $id => $task ) {
					$cli_evaluation[] = array(
						'task_id'     => $id,
						'post_id'     => $post_id,
						'post_title'  => htmlspecialchars_decode( html_entity_decode( get_the_title( $post_id ) ), ENT_QUOTES ),
						'status'      => in_array( $id, $checklist_data['completed'], true ) ? 'complete' : 'incomplete',
						'label'       => $task['label'],
						'explanation' => $task['explanation'],
					);
				}
			}
		}
		if ( 'summary' !== $values['format'] ) {
			\WP_CLI\Utils\format_items( $values['format'], $cli_evaluation, $values['fields'] );
		}
	}
}

WP_CLI::add_command( 'publishing-checklist', 'Publishing_Checklist\CLI_Command' );
