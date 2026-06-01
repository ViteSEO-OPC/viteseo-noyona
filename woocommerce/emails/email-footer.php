<?php
/**
 * Email Footer
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/email-footer.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails
 * @version 10.4.0
 */

defined( 'ABSPATH' ) || exit;

$email = $email ?? null;

?>
																		</div>
																	</td>
																</tr>
															</table>
															<!-- End Content -->
														</td>
													</tr>
												</table>
												<!-- End Body -->
											</td>
										</tr>
									</table>
								</td>
							</tr>
							<tr>
								<td align="center" valign="top">
									<!-- Footer -->
									<table border="0" cellpadding="10" cellspacing="0" width="100%" id="template_footer" role="presentation">
										<tr>
											<td valign="top">
												<table border="0" cellpadding="10" cellspacing="0" width="100%" role="presentation">
													<tr>
														<td colspan="2" valign="middle" id="credit">
															<div class="noyona-email-footer">
																<div class="noyona-email-footer-brand">noyona</div>
																<div class="noyona-email-footer-tagline"><?php esc_html_e( 'BEAUTY ROOTED IN NATURE', 'noyona-childtheme' ); ?></div>
																<?php if ( $email && in_array( $email->id, array( 'customer_reset_password', 'customer_failed_order' ), true ) ) : ?>
																	<p class="noyona-email-footer-links noyona-email-footer-links--social">
																		<a href="https://www.facebook.com/Noyonacosmetics" target="_blank"><?php esc_html_e( 'Facebook /Noyonacosmetics', 'noyona-childtheme' ); ?></a>
																		<span>|</span>
																		<a href="https://www.instagram.com/noyonacosmetics" target="_blank"><?php esc_html_e( 'Instagram /noyonacosmetics', 'noyona-childtheme' ); ?></a>
																		<span>|</span>
																		<a href="https://www.tiktok.com/@noyona_cosmetics" target="_blank"><?php esc_html_e( 'TikTok @noyona_cosmetics', 'noyona-childtheme' ); ?></a>
																		<span>|</span>
																		<a href="https://shopee.ph/noyona_official" target="_blank"><?php esc_html_e( 'Shopee /noyona_official', 'noyona-childtheme' ); ?></a>
																		<span>|</span>
																		<a href="https://www.lazada.com.ph/shop/noyona-lovial-essentials" target="_blank"><?php esc_html_e( 'Lazada /noyona-lovial-essentials', 'noyona-childtheme' ); ?></a>
																	</p>
																<?php else : ?>
																	<p class="noyona-email-footer-links">
																		<a href="<?php echo esc_url( home_url( '/shop/' ) ); ?>" target="_blank"><?php esc_html_e( 'Shop', 'noyona-childtheme' ); ?></a>
																		<span>|</span>
																		<a href="<?php echo esc_url( home_url( '/about-us/' ) ); ?>" target="_blank"><?php esc_html_e( 'About', 'noyona-childtheme' ); ?></a>
																		<span>|</span>
																		<a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>" target="_blank"><?php esc_html_e( 'Contact', 'noyona-childtheme' ); ?></a>
																		<span>|</span>
																		<a href="<?php echo esc_url( home_url( '/faq/' ) ); ?>" target="_blank"><?php esc_html_e( 'FAQ', 'noyona-childtheme' ); ?></a>
																	</p>
																<?php endif; ?>
																<p><?php esc_html_e( 'We accept GCash, Maya, Mastercard, Visa.', 'noyona-childtheme' ); ?></p>
																<?php if ( $email && 'customer_new_account' === $email->id ) : ?>
																	<p><?php esc_html_e( "You're receiving this because an account was created with this email at Noyona Essentials.", 'noyona-childtheme' ); ?></p>
																<?php elseif ( $email && 'customer_reset_password' === $email->id ) : ?>
																	<p><?php esc_html_e( "You're receiving this because a password reset was requested for your Noyona account.", 'noyona-childtheme' ); ?></p>
																<?php elseif ( $email && 'customer_processing_order' === $email->id ) : ?>
																	<p><?php esc_html_e( "You're receiving this because you placed an order with Noyona Essentials.", 'noyona-childtheme' ); ?></p>
																<?php elseif ( $email && 'customer_completed_order' === $email->id ) : ?>
																	<p><?php esc_html_e( "You're receiving this because your Noyona Essentials order was delivered.", 'noyona-childtheme' ); ?></p>
																<?php elseif ( $email && 'customer_failed_order' === $email->id ) : ?>
																	<p><?php esc_html_e( "You're receiving this because a payment was attempted for an order at Noyona Essentials.", 'noyona-childtheme' ); ?></p>
																<?php elseif ( $email && 'customer_cancelled_order' === $email->id ) : ?>
																	<p><?php esc_html_e( "You're receiving this because an order at Noyona Essentials was cancelled.", 'noyona-childtheme' ); ?></p>
																<?php else : ?>
																	<p><?php esc_html_e( "You're receiving this because this email relates to your Noyona Essentials account or order.", 'noyona-childtheme' ); ?></p>
																<?php endif; ?>
																<p>
																	<?php esc_html_e( 'Questions?', 'noyona-childtheme' ); ?>
																	<a href="mailto:info@noyonacosmetics.com">info@noyonacosmetics.com</a>
																	<span>·</span>
																	<?php esc_html_e( 'Noyona Cosmetics & Skin Care Products OPC · Makati City, Philippines', 'noyona-childtheme' ); ?>
																	<span>·</span>
																	<?php if ( $email && 'customer_reset_password' === $email->id ) : ?>
																		<?php esc_html_e( '© 2026 Noyona Essential', 'noyona-childtheme' ); ?>
																	<?php else : ?>
																		<?php esc_html_e( '© 2026 Noyona Essentials.', 'noyona-childtheme' ); ?>
																	<?php endif; ?>
																</p>
															</div>
														</td>
													</tr>
												</table>
											</td>
										</tr>
									</table>
									<!-- End Footer -->
								</td>
							</tr>
						</table>
					</div>
				</td>
				<td><!-- Deliberately empty to support consistent sizing and layout across multiple email clients. --></td>
			</tr>
		</table>
	</body>
</html>
